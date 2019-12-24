<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Orders;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\Orders\Refund
 */
class Refund extends AbstractModel
{
    const MAX_ORDERS_CHANGES_COUNT = 50;

    //########################################

    protected function getNick()
    {
        return '/refund/';
    }

    protected function getTitle()
    {
        return 'Refund';
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
            // The "Refund" action for Walmart account: "%account_title%" is started. Please wait...
            $status = 'The "Refund" action for Walmart account: "%account_title%" is started. Please wait...';
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
                    'The "Refund" Action for Walmart Account "%account%" was completed with error.',
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
            // The "Refund" action for Walmart account: "%account_title%" is finished. Please wait...
            $status = 'The "Refund" action for Walmart account: "%account_title%" is finished. Please wait...';
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
        $accountsCollection = $this->walmartFactory->getObject('Account')->getCollection();
        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $ordersChangesForProcess = $this->getOrdersChangesForProcess($account);
        if (empty($ordersChangesForProcess)) {
            return;
        }

        foreach ($ordersChangesForProcess as $orderChange) {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->walmartFactory->getObjectLoaded('Order', $orderChange->getOrderId());

            $actionHandler = $this->modelFactory->getObject('Walmart_Order_Action_Handler_Refund');
            $actionHandler->setOrder($order);
            $actionHandler->setParams($orderChange->getParams());

            if ($actionHandler->isNeedProcess()) {
                $actionHandler->process();
            }

            $orderChange->delete();
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return \Ess\M2ePro\Model\Order\Change[]
     */
    private function getOrdersChangesForProcess(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Change\Collection $changesCollection */
        $changesCollection = $this->activeRecordFactory->getObject('Order\Change')->getCollection();
        $changesCollection->addAccountFilter($account->getId());
        $changesCollection->addFieldToFilter('component', \Ess\M2ePro\Helper\Component\Walmart::NICK);
        $changesCollection->addFieldToFilter('action', \Ess\M2ePro\Model\Order\Change::ACTION_REFUND);
        $changesCollection->getSelect()->limit(self::MAX_ORDERS_CHANGES_COUNT);
        $changesCollection->getSelect()->group(['order_id']);

        return $changesCollection->getItems();
    }

    //########################################
}
