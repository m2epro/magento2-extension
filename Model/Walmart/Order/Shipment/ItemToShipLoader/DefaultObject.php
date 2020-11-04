<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order\Shipment\ItemToShipLoader;

use Ess\M2ePro\Helper\Data as Helper;
use Ess\M2ePro\Model\AbstractModel;
use Ess\M2ePro\Model\Order\Shipment\ItemToShipLoaderInterface;

/**
 * Class Ess\M2ePro\Model\Walmart\Order\Shipment\ItemToShipLoader\DefaultObject
 */
class DefaultObject extends AbstractModel implements ItemToShipLoaderInterface
{
    /** @var \Ess\M2ePro\Model\Order $order */
    protected $order;

    /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
    protected $shipmentItem;

    /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
    protected $orderItem;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory */
    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;

        list($this->order, $this->shipmentItem) = $data;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @return array
     * @throws \Exception
     */
    public function loadItem()
    {
        $additionalData = $this->getHelper('Data')->unserialize(
            $this->shipmentItem->getOrderItem()->getAdditionalData()
        );

        if ($cache = $this->getAlreadyProcessed($additionalData)) {
            return $cache;
        }

        if (!$this->validate($additionalData)) {
            return [];
        }

        $orderItem = $this->getOrderItem($additionalData);
        $itemQty = $orderItem->getChildObject()->getQtyPurchased();
        $qtyAvailable = (int)$this->shipmentItem->getQty();

        if ($itemQty > $qtyAvailable) {
            $itemQty = $qtyAvailable;
        }

        $orderItemAdditionalData = $orderItem->getAdditionalData();
        $orderItemIdsInShipped = isset($orderItemAdditionalData['order_item_ids_in_shipped']) ?
            $orderItemAdditionalData['order_item_ids_in_shipped'] : [];

        $orderItemIds = array_diff(array_merge(
            [$orderItem->getChildObject()->getWalmartOrderItemId()],
            $orderItem->getChildObject()->getMergedWalmartOrderItemIds()
        ), $orderItemIdsInShipped);

        /**
         * - Walmart returns the same Order Item more than one time with single QTY. That data was merged.
         * - Walmart Order Item QTY is always equals 1.
         */
        $items = [];
        foreach ($orderItemIds as $orderItemId) {
            if ($itemQty <= 0) {
                continue;
            }

            $items[$orderItemId] = [
                'walmart_order_item_id' => $orderItemId,
                'qty'                   => 1
            ];

            $itemQty--;
        }

        $orderItemIdsInShipped += array_keys($items);
        $orderItemAdditionalData['order_item_ids_in_shipped'] = $orderItemIdsInShipped;
        $orderItem->setSettings('additional_data', $orderItemAdditionalData);
        $orderItem->save();

        $additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$this->shipmentItem->getId()] = $items;
        $this->saveAdditionalDataInShipmentItem($additionalData);

        return $items;
    }

    //########################################

    /**
     * @param array $additionalData
     *
     * @return array|null
     */
    protected function getAlreadyProcessed(array $additionalData)
    {
        if (!isset($additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$this->shipmentItem->getId()])) {
            return null;
        }

        return $additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$this->shipmentItem->getId()];
    }

    /**
     * @param array $additionalData
     *
     * @return bool
     */
    protected function validate(array $additionalData)
    {
        if (!isset($additionalData[Helper::CUSTOM_IDENTIFIER]['items']) ||
            !is_array($additionalData[Helper::CUSTOM_IDENTIFIER]['items'])) {
            return false;
        }

        if ($this->shipmentItem->getQty() <= 0) {
            return false;
        }

        if (!isset($additionalData[Helper::CUSTOM_IDENTIFIER]['items'][0]['order_item_id'])) {
            return false;
        }

        $orderItem = $this->getOrderItem($additionalData);
        if (!$orderItem->getId()) {
            return false;
        }

        return true;
    }

    /**
     * @param array $additionalData
     *
     * @throws \Exception
     */
    protected function saveAdditionalDataInShipmentItem(array $additionalData)
    {
        $this->shipmentItem->getOrderItem()->setAdditionalData($this->getHelper('Data')->serialize($additionalData));
        $this->shipmentItem->getOrderItem()->save();
    }

    //########################################

    /**
     * @param array $additionalData
     * @return \Ess\M2ePro\Model\Order\Item
     */
    protected function getOrderItem(array $additionalData)
    {
        if ($this->orderItem !== null) {
            return $this->orderItem;
        }

        $this->orderItem = $this->walmartFactory->getObject('Order_Item')->getCollection()
            ->addFieldToFilter('order_id', $this->order->getId())
            ->addFieldToFilter(
                'walmart_order_item_id',
                $additionalData[Helper::CUSTOM_IDENTIFIER]['items'][0]['order_item_id']
            )
            ->getFirstItem();

        return $this->orderItem;
    }

    //########################################
}
