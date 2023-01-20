<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Feedbacks;

class DownloadNew extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/feedbacks/download_new';

    /** @var int (in seconds) */
    protected $interval = 10800;

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog(): \Ess\M2ePro\Model\Synchronization\Log
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    protected function performActions()
    {
        $accounts = $this->getPermittedAccounts();

        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {
            /** @var \Ess\M2ePro\Model\Account $account * */

            $this->getOperationHistory()->addText('Starting Account "' . $account->getTitle() . '"');

            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'get' . $account->getId(),
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

            $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'get' . $account->getId());
        }
    }

    protected function getPermittedAccounts()
    {
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Ebay::NICK, 'Account')
                                          ->getCollection()
                                          ->addFieldToFilter('feedbacks_receive', 1);

        return $collection->getItems();
    }

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $connection = $this->resource->getConnection();
        $tableFeedbacks = $this->activeRecordFactory->getObject('Ebay\Feedback')->getResource()->getMainTable();

        $dbSelect = $connection->select()
                               ->from($tableFeedbacks, new \Zend_Db_Expr('MAX(`seller_feedback_date`)'))
                               ->where('`account_id` = ?', (int)$account->getId());
        $maxSellerDate = $connection->fetchOne($dbSelect);
        $maxSellerDateTimestamp = (int)$this->helperData
            ->createGmtDateTime($maxSellerDate)
            ->format('U');
        $comparedTimestamp = (int)$this->helperData
            ->createGmtDateTime('2001-01-02')
            ->format('U');
        if ($maxSellerDateTimestamp < $comparedTimestamp) {
            $maxSellerDate = null;
        }

        $dbSelect = $connection->select()
                               ->from($tableFeedbacks, new \Zend_Db_Expr('MAX(`buyer_feedback_date`)'))
                               ->where('`account_id` = ?', (int)$account->getId());
        $maxBuyerDate = $connection->fetchOne($dbSelect);
        $maxBuyerDateTimestamp = (int)$this->helperData
            ->createGmtDateTime($maxBuyerDate)
            ->format('U');
        if ($maxBuyerDateTimestamp < $comparedTimestamp) {
            $maxBuyerDate = null;
        }

        $paramsConnector = [];
        $maxSellerDate !== null && $paramsConnector['seller_max_date'] = $maxSellerDate;
        $maxBuyerDate !== null && $paramsConnector['buyer_max_date'] = $maxBuyerDate;
        $result = $this->receiveFromEbay($account, $paramsConnector);

        $this->getOperationHistory()->appendText('Total received Feedback from eBay: ' . $result['total']);
        $this->getOperationHistory()->appendText('Total only new Feedback from eBay: ' . $result['new']);
        $this->getOperationHistory()->saveBufferString();
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @param array $paramsConnector
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    protected function receiveFromEbay(\Ess\M2ePro\Model\Account $account, array $paramsConnector = []): array
    {
        /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcherObj */
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
            /** @var \Ess\M2ePro\Model\Ebay\Feedback $feedbackObject */
            $feedbackObject = $this->activeRecordFactory->getObject('Ebay\Feedback')->getCollection()
                                                        ->addFieldToFilter('account_id', $account->getId())
                                                        ->addFieldToFilter('ebay_item_id', $feedback['item_id'])
                                                        ->addFieldToFilter(
                                                            'ebay_transaction_id',
                                                            $feedback['transaction_id']
                                                        )
                                                        ->getFirstItem();

            $feedbackObject
                ->setAccountId((int)$account->getId())
                ->setEbayItemId($feedback['item_id'])
                ->setEbayTransactionId($feedback['transaction_id']);

            if ($feedback['item_title'] != '') {
                $feedbackObject->setEbayItemTitle($feedback['item_title']);
            }

            if ($feedback['from_role'] == \Ess\M2ePro\Model\Ebay\Feedback::ROLE_BUYER) {
                $feedbackObject
                    ->setBuyerName($feedback['user_sender'])
                    ->setBuyerFeedbackId($feedback['id'])
                    ->setBuyerFeedbackText($feedback['info']['text'])
                    ->setBuyerFeedbackDate(\Ess\M2ePro\Helper\Date::createDateGmt($feedback['info']['date']))
                    ->setBuyerFeedbackType($feedback['info']['type']);
            } else {
                $feedbackObject
                    ->setSellerFeedbackId($feedback['id'])
                    ->setSellerFeedbackText($feedback['info']['text'])
                    ->setSellerFeedbackDate(\Ess\M2ePro\Helper\Date::createDateGmt($feedback['info']['date']))
                    ->setSellerFeedbackType($feedback['info']['type']);
            }

            if ($feedbackObject->getId() !== null) {
                if (
                    $feedback['from_role'] == \Ess\M2ePro\Model\Ebay\Feedback::ROLE_BUYER
                    && !$feedbackObject->getBuyerFeedbackId()
                ) {
                    $countNewFeedbacks++;
                }

                if (
                    $feedback['from_role'] == \Ess\M2ePro\Model\Ebay\Feedback::ROLE_SELLER
                    && !$feedbackObject->getSellerFeedbackId()
                ) {
                    $countNewFeedbacks++;
                }
            } else {
                $countNewFeedbacks++;
            }

            $feedbackObject->save();
        }

        return [
            'total' => count($feedbacks),
            'new' => $countNewFeedbacks,
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
}
