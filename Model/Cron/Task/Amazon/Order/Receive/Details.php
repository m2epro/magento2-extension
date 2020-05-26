<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\Details
 */
class Details extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/order/receive/details';

    /** @var int $interval (in seconds) */
    protected $interval = 7200;

    //####################################

    /**
     * {@inheritDoc}
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isPossibleToRun()
    {
        if ($this->getHelper('Server_Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //########################################

    /**
     * {@inheritDoc}
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_ORDERS);

        return $synchronizationLog;
    }

    //########################################

    /**
     * {@inheritDoc}
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function performActions()
    {
        $permittedAccounts = $this->getPermittedAccounts();
        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $account) {

            $this->getOperationHistory()->addText('Starting account "' . $account->getTitle() . '"');

            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $account->getId(),
                'Process account ' . $account->getTitle()
            );

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module_Translation')->__(
                    'The "Receive Details" Action for Amazon Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $account->getId());
        }
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getPermittedAccounts()
    {
        /** @var $accountsCollection \Ess\M2ePro\Model\ResourceModel\Account\Collection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account'
        )->getCollection();
        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $from = new \DateTime('now', new \DateTimeZone('UTC'));
        $from->modify('-5 days');

        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Collection $ordersCollection */
        $orderCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Order'
        )->getCollection();
        $orderCollection->getSelect()->joinLeft(
            ['oi' => $this->activeRecordFactory->getObject('Order_Item')->getResource()->getMainTable()],
            'main_table.id = oi.order_id'
        );
        $orderCollection->getSelect()->joinLeft(
            ['aoi' => $this->activeRecordFactory->getObject('Amazon_Order_Item')->getResource()->getMainTable()],
            'oi.id = aoi.order_item_id'
        );
        $orderCollection->addFieldToFilter('aoi.fulfillment_center_id', ['null' => true]);
        $orderCollection->addFieldToFilter('account_id', $account->getId());
        $orderCollection->addFieldToFilter('is_afn_channel', 1);
        $orderCollection->addFieldToFilter('status', ['neq' => \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING]);
        $orderCollection->addFieldToFilter('main_table.create_date', ['gt' => $from->format('Y-m-d H:i:s')]);
        $orderCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $orderCollection->getSelect()->columns('second_table.amazon_order_id');

        $amazonOrdersIds = $orderCollection->getColumnValues('amazon_order_id');
        if (empty($amazonOrdersIds)) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getCustomConnector(
            'Cron_Task_Amazon_Order_Receive_Details_Requester',
            ['items' => $amazonOrdersIds],
            $account
        );
        $dispatcherObject->process($connectorObj);
    }

    //########################################
}
