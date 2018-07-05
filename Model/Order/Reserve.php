<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order;

class Reserve extends \Ess\M2ePro\Model\AbstractModel
{
    const STATE_UNKNOWN  = 0;
    const STATE_PLACED   = 1;
    const STATE_RELEASED = 2;
    const STATE_CANCELED = 3;

    const ACTION_ADD = 'add';
    const ACTION_SUB = 'sub';

    /** @var \Magento\Framework\DB\TransactionFactory  */
    private $transactionFactory = NULL;

    /** @var \Ess\M2ePro\Model\Order */
    private $order = null;

    private $flags = array();

    //########################################

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\Order $order,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->transactionFactory = $transactionFactory;
        $this->order              = $order;
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
            $this->order->addErrorLog(
                'QTY was not reserved. Reason: %msg%', array(
                    'msg' => $e->getMessage()
                )
            );
            return false;
        }

        $this->order->addSuccessLog('QTY has been reserved.');
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
                'QTY was not released. Reason: %msg%', array(
                    'msg' => $e->getMessage()
                )
            );
            return false;
        }

        $this->order->addSuccessLog('QTY has been released.');
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
                'QTY reserve was not canceled. Reason: %msg%', array(
                    'msg' => $e->getMessage()
                )
            );
            return false;
        }

        $this->order->addSuccessLog('QTY reserve has been canceled.');
        return true;
    }

    /**
     * @param $action
     * @param $newState
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function performAction($action, $newState)
    {
        $productsAffectedCount = 0;
        $productsDeletedCount  = 0;
        $productsExistCount    = 0;

        $productStockItems = array();
        $magentoStockItems = array();

        $transaction = $this->transactionFactory->create();

        foreach ($this->order->getItemsCollection()->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Order\Item $item */

            if ($action == self::ACTION_SUB) {
                $qty = $item->getChildObject()->getQtyPurchased();
                $item->setData('qty_reserved', $qty);
            } else {
                $qty = $item->getQtyReserved();
                $item->setData('qty_reserved', 0);
            }

            $products = $this->getItemProductsByAction($item, $action);

            if (count($products) == 0) {
                continue;
            }

            foreach ($products as $key => $productId) {
                /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */

                $magentoProduct = $this->modelFactory->getObject('Magento\Product')
                    ->setStoreId($this->order->getStoreId())
                    ->setProductId($productId);

                if (!$magentoProduct->exists()) {
                    $productsDeletedCount++;
                    unset($products[$key]);
                    continue;
                }

                $productsExistCount++;

                if (!isset($productStockItems[$productId])) {
                    $productStockItems[$productId] = $magentoProduct->getStockItem();
                }

                $stockItem = $productStockItems[$productId];

                /** @var \Ess\M2ePro\Model\Magento\Product\StockItem $magentoStockItem */
                $magentoStockItem = $this->modelFactory->getObject('Magento\Product\StockItem', [
                    'stockItem' => $stockItem
                ]);

                if (!$this->changeProductQty($magentoProduct, $magentoStockItem, $action, $qty)) {
                    if ($action == self::ACTION_SUB) {
                        unset($products[$key]);
                    }

                    continue;
                }

                if ($action == self::ACTION_ADD) {
                    unset($products[$key]);
                }

                $productsAffectedCount++;

                $transaction->addObject($magentoStockItem->getStockItem());

                //--------------------------------------
                if ($item->getMagentoProduct()->isSimpleType() ||
                    $item->getMagentoProduct()->isDownloadableType()) {
                    $item->getProduct()->setStockItem($magentoStockItem->getStockItem());
                }

                /**
                 * After making changes to Stock Item, Magento Product model will contain invalid "salable" status.
                 * Reset Magento Product model for further reload.
                 */
                if ($magentoStockItem->isStockStatusChanged()) {
                    $item->setProduct(NULL);
                }
                //--------------------------------------

                $magentoStockItems[] = $magentoStockItem;
            }

            $item->setReservedProducts($products);
            $transaction->addObject($item);
        }

        unset($productStockItems);

        if ($productsExistCount == 0 && $productsDeletedCount == 0) {
            $this->order->setData('reservation_state', self::STATE_UNKNOWN)->save();
            throw new \Ess\M2ePro\Model\Exception\Logic('The Order Item(s) was not Mapped to Magento Product(s) or
                Mapped incorrect.');
        }

        if ($productsExistCount == 0) {
            $this->order->setData('reservation_state', self::STATE_UNKNOWN)->save();
            throw new \Ess\M2ePro\Model\Exception\Logic('Product(s) does not exist.');
        }

        if ($productsDeletedCount > 0) {
            $this->order->addWarningLog(
                'QTY for %number% Product(s) was not changed. Reason: Product(s) does not exist.',
                array(
                    '!number' => $productsDeletedCount
                )
            );
        }

        if ($productsAffectedCount <= 0) {
            return;
        }

        $this->order->setData('reservation_state', $newState);

        if ($newState == self::STATE_PLACED && !$this->getFlag('order_reservation')) {
            $this->order->setData('reservation_start_date', $this->getHelper('Data')->getCurrentGmtDate());
        }

        $transaction->addObject($this->order)
                    ->save();

        foreach ($magentoStockItems as $magentoStockItem) {
            $magentoStockItem->afterSave();
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @param \Ess\M2ePro\Model\Magento\Product\StockItem $magentoStockItem
     * @param $action
     * @param $qty
     * @return bool
     */
    private function changeProductQty(
        \Ess\M2ePro\Model\Magento\Product $magentoProduct,
        \Ess\M2ePro\Model\Magento\Product\StockItem $magentoStockItem,
        $action,
        $qty
    ) {
        $result = true;

        switch ($action) {

            case self::ACTION_ADD:
                if ($magentoStockItem->canChangeQty()) {
                    $result = $magentoStockItem->addQty($qty, false);
                }
                break;

            case self::ACTION_SUB:
                try {
                    $result = $magentoStockItem->subtractQty($qty, false);
                } catch (\Exception $e) {

                    $this->order->addErrorLog(
                        'QTY for Product "%name%" cannot be reserved. Reason: %msg%',
                        array(
                            '!name' => $magentoProduct->getName(),
                            'msg' => $e->getMessage()
                        )
                    );
                    return false;
                }
                break;
        }

        if ($result === false && $this->order->getLog()->getInitiator() == \Ess\M2ePro\Helper\Data::INITIATOR_USER) {
            $msg = 'The QTY Reservation action (reserve/release/cancel) has not been performed for "%name%" '
                . 'as the "Decrease Stock When Order is Placed" or/and "Manage Stock" options are disabled in your '
                . 'Magento Inventory configurations.';

            $this->order->addWarningLog(
                $msg,
                array('!name' => $magentoProduct->getName())
            );
        }

        return $result;
    }

    /**
     * @param Item $item
     * @param $action
     * @return array|mixed|null
     */
    private function getItemProductsByAction(\Ess\M2ePro\Model\Order\Item $item, $action)
    {
        $products = array();

        switch ($action) {
            case self::ACTION_ADD:
                $products = $item->getReservedProducts();
                break;
            case self::ACTION_SUB:
                if ($item->getProductId() &&
                    ($item->getMagentoProduct()->isSimpleType() ||
                     $item->getMagentoProduct()->isDownloadableType())) {
                    $products[] = $item->getProductId();
                } else {
                    $products = $item->getAssociatedProducts();
                }
                break;
        }

        return $products;
    }

    //########################################
}