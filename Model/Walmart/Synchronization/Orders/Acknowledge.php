<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\Orders;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\Orders\Acknowledge
 */
class Acknowledge extends AbstractModel
{
    const MAX_ORDERS_COUNT = 50;

    //########################################

    protected function getNick()
    {
        return '/acknowledge/';
    }

    protected function getTitle()
    {
        return 'Orders Acknowledge';
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
        $percentsForOneAcc = $this->getPercentsInterval() / count($permittedAccounts);

        foreach ($permittedAccounts as $account) {
            /** @var $account \Ess\M2ePro\Model\Account **/

            try {
                $this->getActualOperationHistory()->addText('Starting account "'.$account->getTitle().'"');

                // M2ePro_TRANSLATIONS
                // The "Create Failed Orders" Action for Walmart Account "%title%" is in Order Creation state...
                $this->getActualLockItem()->setStatus($this->getHelper('Module\Translation')->__(
                    'The "Orders Acknowledge" Action for Walmart Account "%title%" is in Order Creation state...',
                    $account->getTitle()
                ));

                try {
                    $this->processAccount($account);
                } catch (\Exception $exception) {
                    $message = $this->getHelper('Module\Translation')->__(
                        'The "Cancel" Action for Walmart Account "%account%" was completed with error.',
                        $account->getTitle()
                    );

                    $this->processTaskAccountException($message, __FILE__, __LINE__);
                    $this->processTaskException($exception);
                }
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Orders Acknowledge" Action for Walmart Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            // ---------------------------------------
            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneAcc);
            $this->getActualLockItem()->activate();
            // ---------------------------------------

            $iteration++;
        }
    }

    //########################################

    private function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $ordersForProcess = $this->getOrdersForProcess($account);
        if (empty($ordersForProcess)) {
            return;
        }

        foreach ($ordersForProcess as $order) {
            /** @var \Ess\M2ePro\Model\Order $order */

            $actionHandler = $this->modelFactory->getObject('Walmart_Order_Action_Handler_Acknowledge');
            $actionHandler->setOrder($order);

            if ($actionHandler->isNeedProcess()) {
                $actionHandler->process();
            }

            $order->setData('is_tried_to_acknowledge', 1);
            $order->save();
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @return \Ess\M2ePro\Model\Order[]
     */
    private function getOrdersForProcess(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Collection $collection */
        $collection = $this->walmartFactory->getObject('Order')->getCollection();
        $collection->addFieldToFilter('account_id', $account->getId());
        $collection->addFieldToFilter('status', \Ess\M2ePro\Model\Walmart\Order::STATUS_CREATED);
        $collection->addFieldToFilter('is_tried_to_acknowledge', 0);
        $collection->getSelect()->order('purchase_create_date ASC');
        $collection->getSelect()->limit(self::MAX_ORDERS_COUNT);

        return $collection->getItems();
    }

    //########################################

    private function getPermittedAccounts()
    {
        $accountsCollection = $this->walmartFactory->getObject('Account')->getCollection();
        return $accountsCollection->getItems();
    }

    //########################################
}
