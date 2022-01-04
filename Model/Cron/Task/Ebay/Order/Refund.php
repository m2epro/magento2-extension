<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Order;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Order\Refund
 */
class Refund extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/order/refund';

    //########################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

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

    protected function performActions()
    {
        $this->deleteNotActualChanges();

        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $account) {
            /** @var \Ess\M2ePro\Model\Account $account */

            // ---------------------------------------
            $this->getOperationHistory()->addText('Starting account "' . $account->getTitle() . '"');
            // ---------------------------------------

            // ---------------------------------------
            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $account->getId(),
                'Process account ' . $account->getTitle()
            );
            // ---------------------------------------

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Refund" Action for eBay Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            // ---------------------------------------
            $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $account->getId());
            // ---------------------------------------
        }
    }

    //########################################

    protected function getPermittedAccounts()
    {
        /** @var $accountsCollection \Ess\M2ePro\Model\ResourceModel\Account\Collection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Account'
        )->getCollection();

        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $relatedChanges = $this->getRelatedChanges($account);
        if (empty($relatedChanges)) {
            return;
        }

        $this->activeRecordFactory->getObject('Order\Change')->getResource()
            ->incrementAttemptCount(array_keys($relatedChanges));

        /** @var $dispatcherObject \Ess\M2ePro\Model\Ebay\Connector\Dispatcher */
        $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');

        $failedChangesIds = [];

        /** @var \Ess\M2ePro\Model\Order\Change $change */
        foreach ($relatedChanges as $change) {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'Order',
                $change->getOrderId()
            );

            if (!$order->getChildObject()->canRefund()) {
                $failedChangesIds[] = $change->getId();
                continue;
            }

            // ---------------------------------------

            $cancelParams = [
                'order_id'  => $change->getOrderId(),
                'change_id' => $change->getId(),
            ];

            /** @var \Ess\M2ePro\Model\Ebay\Connector\Order\Cancellation\Cancel $connectorObj */
            $connectorObj = $dispatcherObject->getCustomConnector(
                'Ebay_Connector_Order_Cancellation_Cancel',
                $cancelParams,
                $order->getMarketplaceId(),
                $order->getAccountId()
            );
            $dispatcherObject->process($connectorObj);

            $cancelResponseData = $connectorObj->getResponseData();

            if (!$cancelResponseData['result'] && $cancelResponseData['cancel_id'] === null) {
                continue;
            }

            // ---------------------------------------

            $refundParams = [
                'order_id'    => $change->getOrderId(),
                'change_id'   => $change->getId(),
                'cancel_id'   => $cancelResponseData['cancel_id'],
                'refund_date' => (new \DateTime($change->getData('create_date'), new \DateTimeZone('UTC')))
                    ->format('Y-m-d H:i:s')
            ];

            $connectorObj = $dispatcherObject->getCustomConnector(
                'Ebay_Connector_Order_Cancellation_Refund',
                $refundParams,
                $order->getMarketplaceId(),
                $order->getAccountId()
            );
            $dispatcherObject->process($connectorObj);
        }

        if (!empty($failedChangesIds)) {
            $this->activeRecordFactory->getObject('Order\Change')->getResource()->deleteByIds($failedChangesIds);
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return \Ess\M2ePro\Model\Order\Change[]
     */
    protected function getRelatedChanges(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Change\Collection $changesCollection */
        $changesCollection = $this->activeRecordFactory->getObject('Order\Change')->getCollection();
        $changesCollection->addAccountFilter($account->getId());
        $changesCollection->addProcessingAttemptDateFilter();
        $changesCollection->addFieldToFilter('component', \Ess\M2ePro\Helper\Component\Ebay::NICK);
        $changesCollection->addFieldToFilter('action', \Ess\M2ePro\Model\Order\Change::ACTION_REFUND);
        $changesCollection->getSelect()->joinLeft(
            ['pl' => $this->activeRecordFactory->getObject('Processing_Lock')->getResource()->getMainTable()],
            'pl.object_id = main_table.order_id AND pl.model_name = \'Order\'',
            []
        );
        $changesCollection->addFieldToFilter('pl.id', ['null' => true]);
        $changesCollection->getSelect()->group(['order_id']);

        return $changesCollection->getItems();
    }

    //########################################

    protected function deleteNotActualChanges()
    {
        $this->activeRecordFactory->getObject('Order_Change')->getResource()->deleteByProcessingAttemptCount(
            \Ess\M2ePro\Model\Order\Change::MAX_ALLOWED_PROCESSING_ATTEMPTS,
            \Ess\M2ePro\Helper\Component\Ebay::NICK
        );
    }

    //########################################
}
