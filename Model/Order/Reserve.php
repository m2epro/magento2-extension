<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order;

use Ess\M2ePro\Model\Order\Exception\ProductCreationDisabled;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;

/**
 * Class \Ess\M2ePro\Model\Order\Reserve
 */
class Reserve extends \Ess\M2ePro\Model\AbstractModel
{
    const STATE_UNKNOWN  = 0;
    const STATE_PLACED   = 1;
    const STATE_RELEASED = 2;
    const STATE_CANCELED = 3;

    const MAGENTO_RESERVATION_PLACED_EVENT_TYPE   = 'm2epro_reservation_placed';
    const MAGENTO_RESERVATION_RELEASED_EVENT_TYPE = 'm2epro_reservation_released';
    const MAGENTO_RESERVATION_OBJECT_TYPE         = 'm2epro_order';

    const ACTION_ADD = 'add';
    const ACTION_SUB = 'sub';

    /** @var \Magento\Framework\DB\TransactionFactory  */
    private $transactionFactory;

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /** @var \Ess\M2ePro\Model\Order */
    private $order;

    /** @var array */
    private $flags = [];

    private $qtyChangeInfo = [];

    //########################################

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\Order $order,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->objectManager = $objectManager;

        $this->order = $order;

        parent::__construct($helperFactory, $modelFactory);
    }

    public function setFlag($action, $flag)
    {
        $this->flags[$action] = (bool)$flag;
        return $this;
    }

    public function getFlag($action)
    {
        if (isset($this->flags[$action])) {
            return $this->flags[$action];
        }
        return null;
    }

    /**
     * @return bool
     */
    public function isNotProcessed()
    {
        return $this->order->getReservationState() == self::STATE_UNKNOWN;
    }

    /**
     * @return bool
     */
    public function isPlaced()
    {
        return $this->order->getReservationState() == self::STATE_PLACED;
    }

    /**
     * @return bool
     */
    public function isReleased()
    {
        return $this->order->getReservationState() == self::STATE_RELEASED;
    }

    /**
     * @return bool
     */
    public function isCanceled()
    {
        return $this->order->getReservationState() == self::STATE_CANCELED;
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function place()
    {
        if ($this->isPlaced()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('QTY is already reserved.');
        }

        try {
            $this->order->associateWithStore();
            $this->order->associateItemsWithProducts();

            $this->performAction(self::ACTION_SUB, self::STATE_PLACED);
            if (!$this->isPlaced()) {
                return false;
            }
        } catch (\Exception $e) {
            $message = 'QTY was not reserved. Reason: %msg%';
            if ($e instanceof ProductCreationDisabled) {
                $this->order->addInfoLog($message, ['msg' => $e->getMessage()], [], true);

                return false;
            }

            $this->order->addErrorLog($message, ['msg' => $e->getMessage()]);

            return false;
        }

        $this->addSuccessLogQtyChange();
        return true;
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function release()
    {
        if ($this->isReleased()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('QTY is already released.');
        }

        if (!$this->isPlaced()) {
            return false;
        }

        try {

            $this->performAction(self::ACTION_ADD, self::STATE_RELEASED);
            if (!$this->isReleased()) {
                return false;
            }
        } catch (\Exception $e) {
            $this->order->addErrorLog(
                'QTY was not released. Reason: %msg%',
                [
                    'msg' => $e->getMessage()
                ]
            );
            return false;
        }

        $this->addSuccessLogQtyChange();
        return true;
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function cancel()
    {
        if ($this->isCanceled()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('QTY reserve is already canceled.');
        }

        if (!$this->isPlaced()) {
            return false;
        }

        try {

            $this->performAction(self::ACTION_ADD, self::STATE_CANCELED);
            if (!$this->isCanceled()) {
                return false;
            }
        } catch (\Exception $e) {
            $this->order->addErrorLog(
                'QTY reserve was not canceled. Reason: %msg%',
                [
                    'msg' => $e->getMessage()
                ]
            );
            return false;
        }

        $this->addSuccessLogQtyChange();
        $this->order->addSuccessLog('QTY reserve was canceled.');
        return true;
    }

    /**
     * @param $action
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getValidatedOrdersItems($action)
    {
        $productsExistCount  = 0;
        $validatedOrderItems = [];

        foreach ($this->order->getItemsCollection()->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Order\Item $item */

            $products = $this->getItemProductsByAction($item, $action);
            if (empty($products)) {
                continue;
            }

            foreach ($products as $key => $productId) {

                $magentoProduct = $this->modelFactory->getObject('Magento\Product')
                    ->setStoreId($this->order->getStoreId())
                    ->setProductId($productId);

                if (!$magentoProduct->exists()) {
                    $this->order->addWarningLog(
                        'The QTY Reservation action (reserve/release/cancel) has not been performed for
                        Product ID "%id%". It is not exist.',
                        ['!id' => $productId]
                    );
                    continue;
                }

                $productsExistCount++;

                $magentoStockItem = $this->modelFactory->getObject('Magento_Product_StockItem', [
                    'stockItem' => $magentoProduct->getStockItem()
                ]);

                if (!$magentoStockItem->canChangeQty() &&
                    $this->order->getLog()->getInitiator() == \Ess\M2ePro\Helper\Data::INITIATOR_USER
                ) {
                    $this->order->addWarningLog(
                        'The QTY Reservation action (reserve/release/cancel) has not been performed for "%name%"
                        as the "Decrease Stock When Order is Placed" or/and "Manage Stock" options are disabled in
                        your Magento Inventory configurations.',
                        ['!name' => $magentoProduct->getName()]
                    );
                    continue;
                }

                $validatedOrderItems[$item->getId()][$magentoProduct->getProductId()] = [
                    $magentoProduct, $magentoStockItem
                ];
            }
        }

        if ($productsExistCount === 0) {
            $this->order->setData('reservation_state', self::STATE_UNKNOWN)->save();
            throw new \Ess\M2ePro\Model\Exception\Logic('Product(s) does not exist.');
        }

        return $validatedOrderItems;
    }

    /**
     * @param $action
     * @param $newState
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function performAction($action, $newState)
    {
        $productsAffectedCount = 0;
        $productsChangedCount  = 0;
        $validateOrderItems = $this->getValidatedOrdersItems($action);

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();

        foreach ($this->order->getItemsCollection()->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Order\Item $item */

            if ($action === self::ACTION_SUB) {
                $qty = $item->getChildObject()->getQtyPurchased();
                $item->setData('qty_reserved', $qty);
            } else {
                $qty = $item->getQtyReserved();
                $item->setData('qty_reserved', 0);
            }

            $products = isset($validateOrderItems[$item->getId()]) ? $validateOrderItems[$item->getId()] : [];
            if (empty($products)) {
                continue;
            }

            foreach ($products as $productId => $productData) {
                /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
                /** @var \Ess\M2ePro\Model\Magento\Product\StockItem $magentoStockItem */
                list($magentoProduct, $magentoStockItem) = $productData;
                $productsAffectedCount++;

                $changeResult = $this->isMsiMode($magentoProduct)
                    ? $this->changeMSIProductQty($item, $magentoProduct, $magentoStockItem, $action, $qty, $transaction)
                    : $this->changeProductQty($item, $magentoProduct, $magentoStockItem, $action, $qty, $transaction);

                if (!$changeResult) {
                    if ($action === self::ACTION_SUB) {
                        unset($products[$productId]);
                    }

                    continue;
                }

                if ($action === self::ACTION_ADD) {
                    unset($products[$productId]);
                }

                $productsChangedCount++;
                $this->pushQtyChangeInfo($qty, $action, $magentoProduct);
            }

            $item->setReservedProducts(array_keys($products));
            $transaction->addObject($item);
        }

        if ($productsAffectedCount <= 0) {
            return;
        }

        if ($productsChangedCount <= 0 && $action === self::ACTION_SUB) {
            return;
        }

        $this->order->setData('reservation_state', $newState);

        if ($newState === self::STATE_PLACED && !$this->getFlag('order_reservation')) {
            $this->order->setData('reservation_start_date', $this->getHelper('Data')->getCurrentGmtDate());
        }

        $transaction->addObject($this->order);
        $transaction->save();
    }

    protected function changeProductQty(
        \Ess\M2ePro\Model\Order\Item $item,
        \Ess\M2ePro\Model\Magento\Product $magentoProduct,
        \Ess\M2ePro\Model\Magento\Product\StockItem $magentoStockItem,
        $action,
        $qty,
        \Magento\Framework\DB\Transaction $transaction
    ) {
        if (!$magentoStockItem->canChangeQty()) {
            return false;
        }

        $result = false;

        if ($action === self::ACTION_ADD) {
            $result = $magentoStockItem->addQty($qty, false);
        }

        if ($action === self::ACTION_SUB) {
            $result = $magentoStockItem->subtractQty($qty, false);

            if (!$result &&
                !$magentoStockItem->isAllowedQtyBelowZero() &&
                $magentoStockItem->resultOfSubtractingQtyBelowZero($qty)
            ) {
                $this->order->addErrorLog(
                    'QTY wasn’t reserved for "%name%". Magento QTY: "%magento_qty%". Ordered QTY: "%order_qty%".',
                    [
                        '!name'        => $magentoProduct->getName(),
                        '!magento_qty' => $magentoStockItem->getStockItem()->getQty(),
                        '!order_qty'   => $qty,
                    ]
                );
            }
        }

        if (!$result) {
            return false;
        }

        $transaction->addObject($magentoStockItem->getStockItem());
        $transaction->addCommitCallback([$magentoStockItem, 'afterSave']);

        //--------------------------------------
        if ($magentoProduct->isSimpleType() || $magentoProduct->isDownloadableType()) {
            $item->getProduct()->setStockItem($magentoStockItem->getStockItem());
        }

        /**
         * After making changes to Stock Item, Magento Product model will contain invalid "salable" status.
         * Reset Magento Product model for further reload.
         */
        if ($magentoStockItem->isStockStatusChanged()) {
            $item->setProduct(null);
        }
        //--------------------------------------

        return $result;
    }

    protected function changeMSIProductQty(
        \Ess\M2ePro\Model\Order\Item $item,
        \Ess\M2ePro\Model\Magento\Product $magentoProduct,
        \Ess\M2ePro\Model\Magento\Product\StockItem $magentoStockItem,
        $action,
        $qty,
        \Magento\Framework\DB\Transaction $transaction
    ) {
        $reservationMarkPath = "reservation_msi_used/{$magentoProduct->getProductId()}";

        try {

            if ($action === self::ACTION_ADD) {
                if (!$item->getSetting('product_details', $reservationMarkPath, false)) {
                    return $this->changeProductQty($item, $magentoProduct, $magentoStockItem, $action, $qty, $transaction);
                }
                $item->setSetting('product_details', $reservationMarkPath, null);
            }

            $stockByWebsiteIdResolver = $this->objectManager->get(StockByWebsiteIdResolverInterface::class);
            $websiteId = (int)$item->getOrder()->getStore()->getWebsiteId();
            $stockId   = (int)$stockByWebsiteIdResolver->execute($websiteId)->getStockId();

            if ($action === self::ACTION_SUB) {
                $checkItemsQty = $this->objectManager->get(\Magento\InventorySales\Model\CheckItemsQuantity::class);
                $checkItemsQty->execute([$magentoProduct->getSku() => $qty], $stockId);

                $item->setSetting('product_details', $reservationMarkPath, true);
            }
        } catch (\Exception $e) {
            $message = $action === self::ACTION_SUB
                ? 'QTY for Product "%name%" cannot be reserved. Reason: %msg%'
                : 'QTY reservation for Product "%name%" cannot be released. Reason: %msg%';
            $this->order->addErrorLog(
                $message,
                [
                    '!name' => $magentoProduct->getName(),
                    '!msg' => $e->getMessage()
                ]
            );
            return false;
        }

        $reservation = $this->objectManager->get(\Ess\M2ePro\Model\MSI\Order\Reserve::class);
        $reservation->placeCompensationReservation(
            [[
                'sku' => $magentoProduct->getSku(),
                'qty' => $action === self::ACTION_SUB ? -$qty : $qty
            ]],
            $this->order->getStoreId(),
            [
                'type' => $action === self::ACTION_SUB ? $reservation::EVENT_TYPE_MAGENTO_RESERVATION_PLACED
                                                       : $reservation::EVENT_TYPE_MAGENTO_RESERVATION_RELEASED,
                'objectType' => $reservation::M2E_ORDER_OBJECT_TYPE,
                'objectId'   => (string)$this->order->getId()
            ]
        );

        $key = 'released_reservation_product_' . $magentoProduct->getSku() . '_' . $stockId;
        if ($action === self::ACTION_ADD && !$this->getHelper('Data\GlobalData')->getValue($key)) {
            $this->getHelper('Data\GlobalData')->setValue($key, true);
        }

        return true;
    }

    protected function pushQtyChangeInfo($qty, $action, \Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $this->qtyChangeInfo[] = [
            'action'       => $action,
            'quantity'     => $qty,
            'product_name' => $magentoProduct->getName()
        ];
    }

    protected function addSuccessLogQtyChange()
    {
        $description = [
            self::ACTION_ADD => 'QTY was released for "%product_name%". Released QTY: %quantity%.',
            self::ACTION_SUB => 'QTY was reserved for "%product_name%". Reserved QTY: %quantity%.'
        ];

        foreach ($this->qtyChangeInfo as $item) {
            $this->order->addSuccessLog(
                $description[$item['action']],
                [
                    '!product_name' => $item['product_name'],
                    '!quantity'    => $item['quantity']
                ]
            );
        }

        $this->qtyChangeInfo = [];
    }

    /**
     * @param Item $item
     * @param $action
     * @return array|mixed|null
     */
    private function getItemProductsByAction(\Ess\M2ePro\Model\Order\Item $item, $action)
    {
        switch ($action) {
            case self::ACTION_ADD:
                return $item->getReservedProducts();

            case self::ACTION_SUB:
                if ($item->getProductId() &&
                    ($item->getMagentoProduct()->isSimpleType() || $item->getMagentoProduct()->isDownloadableType())
                ) {
                    return [$item->getProductId()];
                }
                return $item->getAssociatedProducts();
        }
    }

    //########################################

    private function isMsiMode(\Ess\M2ePro\Model\Magento\Product $product)
    {
        if (!$this->getHelper('Magento')->isMSISupportingVersion()) {
            return false;
        }

        if (interface_exists(IsSourceItemManagementAllowedForProductTypeInterface::class)) {
            $isSourceItemManagementAllowedForProductType = $this->objectManager->get(
                IsSourceItemManagementAllowedForProductTypeInterface::class
            );
            return $isSourceItemManagementAllowedForProductType->execute($product->getTypeId());
        }

        return true;
    }

    //########################################
}
