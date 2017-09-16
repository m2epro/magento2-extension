<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\General\Feedbacks;

class Receive extends AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/feedbacks/receive/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Receive';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 0;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 30;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    protected function intervalIsEnabled()
    {
        return true;
    }

    //########################################

    protected function performActions()
    {
        $accounts = $this->getPermittedAccounts();

        if (count($accounts) <= 0) {
            return;
        }

        $iteration = 1;
        $percentsForOneStep = $this->getPercentsInterval() / count($accounts);

        foreach ($accounts as $account) {

            /** @var $account \Ess\M2ePro\Model\Account **/

            $this->getActualOperationHistory()->addText('Starting Account "'.$account->getTitle().'"');
            // M2ePro\TRANSLATIONS
            // The "Receive" Action for eBay Account: "%account_title%" is started. Please wait...
            $status = 'The "Receive" Action for eBay Account: "%account_title%" is started. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );

            $this->getActualOperationHistory()->addTimePoint(
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
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'get'.$account->getId());

            // M2ePro\TRANSLATIONS
            // The "Receive" Action for eBay Account: "%account_title%" is finished. Please wait...
            $status = 'The "Receive" Action for eBay Account: "%account_title%" is finished. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneStep);
            $this->getActualLockItem()->activate();

            $iteration++;
        }
    }

    //########################################

    protected function getPermittedAccounts()
    {
        $collection = $this->ebayFactory->getObject('Account')->getCollection()
                                    ->addFieldToFilter('feedbacks_receive',
                                                       \Ess\M2ePro\Model\Ebay\Account::FEEDBACKS_RECEIVE_YES);
        return $collection->getItems();
    }

    // ---------------------------------------

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $connRead = $this->resourceConnection->getConnection();
        $tableFeedbacks = $this->activeRecordFactory->getObject('Ebay\Feedback')->getResource()->getMainTable();

        $dbSelect = $connRead->select()
                             ->from($tableFeedbacks,new \Zend_Db_Expr('MAX(`seller_feedback_date`)'))
                             ->where('`account_id` = ?',(int)$account->getId());
        $maxSellerDate = $connRead->fetchOne($dbSelect);
        if (strtotime($maxSellerDate) < strtotime('2001-01-02')) {
            $maxSellerDate = NULL;
        }

        $dbSelect = $connRead->select()
                             ->from($tableFeedbacks,new \Zend_Db_Expr('MAX(`buyer_feedback_date`)'))
                             ->where('`account_id` = ?',(int)$account->getId());
        $maxBuyerDate = $connRead->fetchOne($dbSelect);
        if (strtotime($maxBuyerDate) < strtotime('2001-01-02')) {
            $maxBuyerDate = NULL;
        }

        $paramsConnector = array();
        !is_null($maxSellerDate) && $paramsConnector['seller_max_date'] = $maxSellerDate;
        !is_null($maxBuyerDate) && $paramsConnector['buyer_max_date'] = $maxBuyerDate;
        $result = $this->receiveFromEbay($account,$paramsConnector);

        $this->getActualOperationHistory()->appendText('Total received Feedback from eBay: '.$result['total']);
        $this->getActualOperationHistory()->appendText('Total only new Feedback from eBay: '.$result['new']);
        $this->getActualOperationHistory()->saveBufferString();
    }

    protected function receiveFromEbay(\Ess\M2ePro\Model\Account $account, array $paramsConnector = array())
    {
        $dispatcherObj = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector('feedback','get','entity',
                                                            $paramsConnector,'feedbacks',
                                                            NULL,$account->getId());

        $dispatcherObj->process($connectorObj);
        $feedbacks = $connectorObj->getResponseData();
        $this->processResponseMessages($connectorObj->getResponseMessages());

        is_null($feedbacks) && $feedbacks = array();

        $countNewFeedbacks = 0;
        foreach ($feedbacks as $feedback) {

            $dbFeedback = array(
                'account_id' => $account->getId(),
                'ebay_item_id' => $feedback['item_id'],
                'ebay_transaction_id' => $feedback['transaction_id']
            );

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

            if (!is_null($existFeedback->getId())) {

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

        return array(
            'total' => count($feedbacks),
            'new'   => $countNewFeedbacks
        );
    }

    private function processResponseMessages(array $messages)
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message\Set $messagesSet */
        $messagesSet = $this->modelFactory->getObject('Connector\Connection\Response\Message\Set');
        $messagesSet->init($messages);

        foreach ($messagesSet->getEntities() as $message) {

            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    //########################################
}