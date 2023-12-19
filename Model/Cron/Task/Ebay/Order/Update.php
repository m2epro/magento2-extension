<?php

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Order;

use Ess\M2ePro\Model\Order\Change;

class Update extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/order/update';

    public const MAX_UPDATES_PER_TIME = 200;

    /** @var \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Dispatcher */
    private $orderItemConnectorDispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Dispatcher $orderItemConnectorDispatcher,
        \Ess\M2ePro\Model\Cron\Manager $cronManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct(
            $cronManager,
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );

        $this->orderItemConnectorDispatcher = $orderItemConnectorDispatcher;
    }

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

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

    protected function performActions()
    {
        $this->deleteNotActualChanges();

        $permittedAccounts = $this->getPermittedAccounts();

        if (empty($permittedAccounts)) {
            return;
        }

        foreach ($permittedAccounts as $account) {
            /** @var \Ess\M2ePro\Model\Account $account * */

            $this->getOperationHistory()->addText('Starting Account "' . $account->getTitle() . '"');

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Update" Action for eBay Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }
        }
    }

    protected function getPermittedAccounts()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Account'
        )->getCollection();

        return $accountsCollection->getItems();
    }

    // ---------------------------------------

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $changes = $this->getRelatedChanges($account);
        if (empty($changes)) {
            return;
        }

        foreach ($changes as $change) {
            $this->processChange($change);
        }
    }

    protected function getRelatedChanges(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Change\Collection $changesCollection */
        $changesCollection = $this->activeRecordFactory->getObject('Order\Change')->getCollection();
        $changesCollection->addAccountFilter($account->getId());
        $changesCollection->addProcessingAttemptDateFilter();
        $changesCollection->addFieldToFilter('action', [
            'in' => [
                Change::ACTION_UPDATE_SHIPPING,
                Change::ACTION_UPDATE_PAYMENT,
            ],
        ]);
        $changesCollection->setPageSize(self::MAX_UPDATES_PER_TIME);
        $changesCollection->getSelect()->group(['order_id']);

        return $changesCollection->getItems();
    }

    // ---------------------------------------

    protected function processChange(\Ess\M2ePro\Model\Order\Change $change)
    {
        $this->activeRecordFactory->getObject('Order\Change')->getResource()
                                  ->incrementAttemptCount([$change->getId()]);
        $connectorData = ['change_id' => $change->getId()];

        if ($change->isPaymentUpdateAction()) {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'Order',
                $change->getOrderId()
            );

            if ($order->getId()) {
                $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Order_Dispatcher');
                $dispatcher->process(
                    \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_PAY,
                    [$order],
                    $connectorData
                );
            }

            return;
        }

        if ($change->isShippingUpdateAction()) {
            $changeParams = $change->getParams();

            $action = \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP;
            if (!empty($changeParams['tracking_number']) && !empty($changeParams['carrier_title'])) {
                $action = \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher::ACTION_SHIP_TRACK;
                /**
                 * TODO check(rewrite) during orders refactoring.
                 * \Ess\M2ePro\Model\Ebay\Connector\Order\Dispatcher expects array of order to be proccessed.
                 * But $connectorData has no link to order instance, so appears like discrepancy between these
                 * two parameters.
                 */
                $connectorData['tracking_number'] = $changeParams['tracking_number'];
                $connectorData['carrier_code'] = $changeParams['carrier_title'];
            }

            if ($shippingItemDataChanges = $this->getPreparedShippingItemDataChanges($change)) {
                $result = empty($connectorData['carrier_code']) || empty($connectorData['tracking_number'])
                    ? $this->updateShippingStatusForEachItems($shippingItemDataChanges)
                    : $this->updateShippingStatusForPackOfItems(
                        $shippingItemDataChanges,
                        $connectorData['tracking_number'],
                        $connectorData['carrier_code']
                    );

                if ($result) {
                    $change->delete();
                }

                return;
            }

            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'Order',
                $change->getOrderId()
            );

            if ($order->getId()) {
                $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Order_Dispatcher');
                $dispatcher->process($action, [$order], $connectorData);
            }
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Order\Change $change
     *
     * @return list<array{order_item: \Ess\M2ePro\Model\Order\Item, shipped_qty: int}>
     */
    private function getPreparedShippingItemDataChanges(\Ess\M2ePro\Model\Order\Change $change): array
    {
        $changeParams = $change->getParams();
        if (empty($changeParams['items'])) {
            return [];
        }

        $shippingData = [];
        foreach ($changeParams['items'] as $itemData) {
            if (empty($itemData['item_id']) || empty($itemData['shipped_qty'])) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
            $orderItem = $this->parentFactory->getObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'Order\Item',
                $itemData['item_id']
            );

            if (
                $orderItem->getId() === null
                || $orderItem->getOrderId() != $change->getOrderId()
            ) {
                continue;
            }

            /** @see \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Update\Status */
            $orderItem->getOrder()->getLog()->setInitiator(
                $change->getCreatorType()
            );

            $shippingData[] = [
                'order_item' => $orderItem,
                'shipped_qty' => $itemData['shipped_qty'],
            ];
        }

        return $shippingData;
    }

    private function updateShippingStatusForEachItems(array $orderItemsShippingParams): bool
    {
        $isSuccessful = true;
        foreach ($orderItemsShippingParams as $shippingParam) {
            /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
            $orderItem = $shippingParam['order_item'];

            $request = new \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Update\Request();
            $request->setItemId($orderItem->getChildObject()->getItemId());
            $request->setTransactionId($orderItem->getChildObject()->getTransactionId());

            $result = $this->orderItemConnectorDispatcher->process(
                \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Dispatcher::ACTION_UPDATE_STATUS,
                [$orderItem],
                [\Ess\M2ePro\Model\Ebay\Connector\OrderItem\Update\Status::REQUEST_PARAM_KEY => $request]
            );

            if (!$result) {
                $isSuccessful = false;
            }
        }

        return $isSuccessful;
    }

    private function updateShippingStatusForPackOfItems(
        array $orderItemsShippingParams,
        string $trackingNumber,
        string $carrierCode
    ): bool {
        $request = new \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Update\Request();
        $firstOrderItem = $orderItemsShippingParams[0]['order_item'];
        $request->setOrderId($firstOrderItem->getOrder()->getChildObject()->getEbayOrderId());

        foreach ($orderItemsShippingParams as $shippingParam) {
            /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
            $orderItem = $shippingParam['order_item'];
            $request->addItem(
                new \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Update\Request\Item(
                    $orderItem->getChildObject()->getItemId(),
                    $orderItem->getChildObject()->getTransactionId(),
                    $trackingNumber,
                    $carrierCode,
                    $shippingParam['shipped_qty']
                )
            );
        }

        return $this->orderItemConnectorDispatcher->process(
            \Ess\M2ePro\Model\Ebay\Connector\OrderItem\Dispatcher::ACTION_UPDATE_TRACK,
            [$firstOrderItem],
            [\Ess\M2ePro\Model\Ebay\Connector\OrderItem\Update\Status::REQUEST_PARAM_KEY => $request]
        );
    }

    protected function deleteNotActualChanges()
    {
        $this->activeRecordFactory->getObject('Order\Change')->getResource()->deleteByProcessingAttemptCount(
            \Ess\M2ePro\Model\Order\Change::MAX_ALLOWED_PROCESSING_ATTEMPTS,
            \Ess\M2ePro\Helper\Component\Ebay::NICK
        );
    }
}
