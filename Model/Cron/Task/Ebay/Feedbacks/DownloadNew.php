<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Feedbacks;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Feedbacks\DownloadNew
 */
class DownloadNew extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/feedbacks/download_new';

    /**
     * @var int (in seconds)
     */
    protected $interval = 10800;

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    //########################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //########################################

    protected function performActions()
    {
        $accounts = $this->getPermittedAccounts();

        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');

            $this->getOperationHistory()->addTimePoint(
                __METHOD__.'get'.$account->getId(),
                'Get feedbacks from eBay'
            );

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Receive" Action for eBay Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__.'get'.$account->getId());
        }
    }

    //########################################

    protected function getPermittedAccounts()
    {
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Ebay::NICK, 'Account')
            ->getCollection()
            ->addFieldToFilter('feedbacks_receive', 1);
        return $collection->getItems();
    }

    // ---------------------------------------

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $connection = $this->resource->getConnection();
        $tableFeedbacks = $this->activeRecordFactory->getObject('Ebay\Feedback')->getResource()->getMainTable();

        $dbSelect = $connection->select()
            ->from($tableFeedbacks, new \Zend_Db_Expr('MAX(`seller_feedback_date`)'))
            ->where('`account_id` = ?', (int)$account->getId());
        $maxSellerDate = $connection->fetchOne($dbSelect);
        if (strtotime($maxSellerDate) < strtotime('2001-01-02')) {
            $maxSellerDate = null;
        }

        $dbSelect = $connection->select()
            ->from($tableFeedbacks, new \Zend_Db_Expr('MAX(`buyer_feedback_date`)'))
            ->where('`account_id` = ?', (int)$account->getId());
        $maxBuyerDate = $connection->fetchOne($dbSelect);
        if (strtotime($maxBuyerDate) < strtotime('2001-01-02')) {
            $maxBuyerDate = null;
        }

        $paramsConnector = [];
        $maxSellerDate !== null && $paramsConnector['seller_max_date'] = $maxSellerDate;
        $maxBuyerDate !== null && $paramsConnector['buyer_max_date'] = $maxBuyerDate;
        $result = $this->receiveFromEbay($account, $paramsConnector);

        $this->getOperationHistory()->appendText('Total received Feedback from eBay: '.$result['total']);
        $this->getOperationHistory()->appendText('Total only new Feedback from eBay: '.$result['new']);
        $this->getOperationHistory()->saveBufferString();
    }

    protected function receiveFromEbay(\Ess\M2ePro\Model\Account $account, array $paramsConnector = [])
    {
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'feedback',
            'get',
            'entity',
            $paramsConnector,
            'feedbacks',
            null,
            $account->getId()
        );

        $dispatcherObj->process($connectorObj);
        $feedbacks = $connectorObj->getResponseData();
        $this->processResponseMessages($connectorObj->getResponseMessages());

        $feedbacks === null && $feedbacks = [];

        $countNewFeedbacks = 0;
        foreach ($feedbacks as $feedback) {
            $dbFeedback = [
                'account_id' => $account->getId(),
                'ebay_item_id' => $feedback['item_id'],
                'ebay_transaction_id' => $feedback['transaction_id']
            ];

            if ($feedback['item_title'] != '') {
                $dbFeedback['ebay_item_title'] = $feedback['item_title'];
            }

            if ($feedback['from_role'] == \Ess\M2ePro\Model\Ebay\Feedback::ROLE_BUYER) {
                $dbFeedback['buyer_name'] = $feedback['user_sender'];
                $dbFeedback['buyer_feedback_id'] = $feedback['id'];
                $dbFeedback['buyer_feedback_text'] = $feedback['info']['text'];
                $dbFeedback['buyer_feedback_date'] = $feedback['info']['date'];
                $dbFeedback['buyer_feedback_type'] = $feedback['info']['type'];
            } else {
                $dbFeedback['seller_feedback_id'] = $feedback['id'];
                $dbFeedback['seller_feedback_text'] = $feedback['info']['text'];
                $dbFeedback['seller_feedback_date'] = $feedback['info']['date'];
                $dbFeedback['seller_feedback_type'] = $feedback['info']['type'];
            }

            $existFeedback = $this->activeRecordFactory->getObject('Ebay\Feedback')->getCollection()
                ->addFieldToFilter('account_id', $account->getId())
                ->addFieldToFilter('ebay_item_id', $feedback['item_id'])
                ->addFieldToFilter('ebay_transaction_id', $feedback['transaction_id'])
                ->getFirstItem();

            if ($existFeedback->getId() !== null) {
                if ($feedback['from_role'] == \Ess\M2ePro\Model\Ebay\Feedback::ROLE_BUYER &&
                    !$existFeedback->getData('buyer_feedback_id')) {
                    $countNewFeedbacks++;
                }

                if ($feedback['from_role'] == \Ess\M2ePro\Model\Ebay\Feedback::ROLE_SELLER &&
                    !$existFeedback->getData('seller_feedback_id')) {
                    $countNewFeedbacks++;
                }
            } else {
                $countNewFeedbacks++;
            }

            $existFeedback->addData($dbFeedback)->save();
        }

        return [
            'total' => count($feedbacks),
            'new'   => $countNewFeedbacks
        ];
    }

    protected function processResponseMessages(array $messages)
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector_Connection_Response_Message_Set');
        $messagesSet->init($messages);

        foreach ($messagesSet->getEntities() as $message) {
            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType
            );
        }
    }

    //########################################
}
