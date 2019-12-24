<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\Orders;

/**
 * Class \Ess\M2ePro\Model\Amazon\Synchronization\Orders\Cancel
 */
class Cancel extends AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/cancel/';
    }

    protected function getTitle()
    {
        return 'Cancel';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function performActions()
    {
        $this->deleteNotActualChanges();

        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        $iteration = 0;
        $percentsForOneStep = $this->getPercentsInterval() / count($permittedAccounts);

        foreach ($permittedAccounts as $account) {

            /** @var \Ess\M2ePro\Model\Account $account */

            // ---------------------------------------
            $this->getActualOperationHistory()->addText('Starting account "'.$account->getTitle().'"');
            // M2ePro\TRANSLATIONS
            // The "Cancel" action for Amazon account: "%account_title%" is started. Please wait...
            $status = 'The "Cancel" action for Amazon account: "%account_title%" is started. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            // ---------------------------------------

            // ---------------------------------------
            $this->getActualOperationHistory()->addTimePoint(
                __METHOD__.'process'.$account->getId(),
                'Process account '.$account->getTitle()
            );
            // ---------------------------------------

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Cancel" Action for Amazon Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            // ---------------------------------------
            $this->getActualOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
            // ---------------------------------------

            // ---------------------------------------
            // M2ePro\TRANSLATIONS
            // The "Cancel" action for Amazon account: "%account_title%" is finished. Please wait...
            $status = 'The "Cancel" action for Amazon account: "%account_title%" is finished. Please wait...';
            $this->getActualLockItem()->setStatus(
                $this->getHelper('Module\Translation')->__($status, $account->getTitle())
            );
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneStep);
            $this->getActualLockItem()->activate();
            // ---------------------------------------

            $iteration++;
        }
    }

    //########################################

    private function getPermittedAccounts()
    {
        $accountsCollection = $this->amazonFactory->getObject('Account')->getCollection();
        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $relatedChanges = $this->getRelatedChanges($account);
        if (empty($relatedChanges)) {
            return;
        }

        $this->activeRecordFactory->getObject('Order\Change')->getResource()
            ->incrementAttemptCount(array_keys($relatedChanges));

        /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');

        $failedChangesIds = [];

        foreach ($relatedChanges as $change) {
            $changeParams = $change->getParams();

            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->amazonFactory->getObjectLoaded('Order', $change->getOrderId());

            /** @var \Ess\M2ePro\Model\Amazon\Order $amazonOrder */
            $amazonOrder = $order->getChildObject();

            if (!$amazonOrder->canRefund()) {
                $failedChangesIds[] = $change->getId();
                continue;
            }

            $connectorData = [
                'order_id'        => $change->getOrderId(),
                'change_id'       => $change->getId(),
                'amazon_order_id' => $changeParams['order_id'],
            ];

            $connectorObj = $dispatcherObject->getCustomConnector(
                'Amazon_Synchronization_Orders_Cancel_Requester',
                ['order' => $connectorData],
                $account
            );
            $dispatcherObject->process($connectorObj);
        }

        if (!empty($failedChangesIds)) {
            $this->activeRecordFactory->getObject('Order\Change')->getResource()
                ->deleteByIds($failedChangesIds);
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return \Ess\M2ePro\Model\Order\Change[]
     */
    private function getRelatedChanges(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Change\Collection $changesCollection */
        $changesCollection = $this->activeRecordFactory->getObject('Order\Change')->getCollection();
        $changesCollection->addAccountFilter($account->getId());
        $changesCollection->addProcessingAttemptDateFilter(10);
        $changesCollection->addFieldToFilter('component', \Ess\M2ePro\Helper\Component\Amazon::NICK);
        $changesCollection->addFieldToFilter('action', \Ess\M2ePro\Model\Order\Change::ACTION_CANCEL);
        $changesCollection->getSelect()->group(['order_id']);

        return $changesCollection->getItems();
    }

    // ---------------------------------------

    private function deleteNotActualChanges()
    {
        $this->activeRecordFactory->getObject('Order\Change')->getResource()
            ->deleteByProcessingAttemptCount(
                \Ess\M2ePro\Model\Order\Change::MAX_ALLOWED_PROCESSING_ATTEMPTS,
                \Ess\M2ePro\Helper\Component\Amazon::NICK
            );
    }

    //########################################
}
