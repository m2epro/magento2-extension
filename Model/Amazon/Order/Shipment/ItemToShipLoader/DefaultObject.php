<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Shipment\ItemToShipLoader;

use Ess\M2ePro\Helper\Data as DataHelper;
use Ess\M2ePro\Model\AbstractModel;
use Ess\M2ePro\Model\Order\Shipment\ItemToShipLoaderInterface;

/**
 * Class Ess\M2ePro\Model\Amazon\Order\Shipment\ItemToShipLoader\DefaultObject
 */
class DefaultObject extends AbstractModel implements ItemToShipLoaderInterface
{
    /** @var \Ess\M2ePro\Model\Order $order */
    protected $order;

    /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
    protected $shipmentItem;

    /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
    protected $orderItem;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory */
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;

        [$this->order, $this->shipmentItem] = $data;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    // ----------------------------------------

    /**
     * @return array
     * @throws \Exception
     */
    public function loadItem()
    {
        $additionalData = $this->helperFactory
            ->getObjectByClass(DataHelper::class)
            ->unserialize($this->shipmentItem->getOrderItem()->getAdditionalData());

        if ($cache = $this->getAlreadyProcessed($additionalData)) {
            return $cache;
        }

        $qtyShipped = (int)$this->shipmentItem->getQty();
        if ($qtyShipped <= 0) {
            return [];
        }

        $availableOrderItems = $this
            ->getAvailableOrderItems($additionalData[DataHelper::CUSTOM_IDENTIFIER] ?? []);

        foreach ($availableOrderItems as $amazonOrderItemId => $availableToShipQty) {
            $requestQty = $availableToShipQty;
            if ($availableToShipQty > $qtyShipped) {
                $requestQty = $qtyShipped;
            }

            $resultItems[] =  [
                'amazon_order_item_id' => $amazonOrderItemId,
                'qty' => $requestQty,
            ];

            $qtyShipped -= $requestQty;
            if ($qtyShipped <= 0) {
                break;
            }
        }

        if (empty($resultItems)) {
            return [];
        }

        $additionalData[DataHelper::CUSTOM_IDENTIFIER]['shipments'][$this->shipmentItem->getId()] = $resultItems;
        $this->saveAdditionalDataInShipmentItem($additionalData);

        return $resultItems;
    }

    // ----------------------------------------

    /**
     * @param array $additionalData
     *
     * @return array|null
     */
    protected function getAlreadyProcessed(array $additionalData): ?array
    {
        if (!isset($additionalData[DataHelper::CUSTOM_IDENTIFIER]['shipments'][$this->shipmentItem->getId()])) {
            return null;
        }

        return $additionalData[DataHelper::CUSTOM_IDENTIFIER]['shipments'][$this->shipmentItem->getId()];
    }

    /**
     * @param array $additionalData
     *
     * @throws \Exception
     */
    protected function saveAdditionalDataInShipmentItem(array $additionalData)
    {
        $additionalData = $this->helperFactory
            ->getObjectByClass(DataHelper::class)
            ->serialize($additionalData);

        $this->shipmentItem
            ->getOrderItem()
            ->setAdditionalData($additionalData)
            ->save();
    }

    // ----------------------------------------

    /**
     * @return array Key - amazon order item id, value - available to ship QTY.
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getAvailableOrderItems(array $additionalData): array
    {
        $additionalDataItems = $this->prepareOrderItems($additionalData['items'] ?? []);
        $additionalDataShipments = $this->prepareShipments($additionalData['shipments'] ?? []);

        $result = [];
        foreach ($additionalDataItems as $orderItemId => $purchasedQty) {
            $shippedQty = $additionalDataShipments[$orderItemId] ?? 0;
            if ($shippedQty >= $purchasedQty) {
                continue;
            }

            $result[$orderItemId] = $purchasedQty - $shippedQty;
        }

        return $result;
    }

    /**
     * @return array Key - amazon order item id, value - purchased QTY
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function prepareOrderItems(array $orderItemsData): array
    {
        $result = [];
        foreach ($orderItemsData as $orderItemData) {
            $amazonOrderItemId = (string)($orderItemData['order_item_id'] ?? '');
            $orderItem = $this->getOrderItemByAmazonOrderItemId($amazonOrderItemId);

            if (empty($orderItem)) {
                continue;
            }

            $result[$amazonOrderItemId] = $orderItem->getChildObject()->getQtyPurchased();
        }

        return $result;
    }

    private function getOrderItemByAmazonOrderItemId(string $amazonOrderItemId): ?\Ess\M2ePro\Model\Order\Item
    {
        if (empty($amazonOrderItemId)) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Order\Item $result */
        $result = $this->amazonFactory
            ->getObject('Order_Item')
            ->getCollection()
            ->addFieldToFilter('order_id', $this->order->getId())
            ->addFieldToFilter(
                'amazon_order_item_id',
                ['eq' => $amazonOrderItemId]
            )
            ->getFirstItem();

        if ($result->isObjectNew()) {
            return null;
        }

        return $result;
    }

    /**
     * @return array Key - amazon order item ID, value - shipped QTY.
     */
    private function prepareShipments(array $shipments): array
    {
        $result = [];
        foreach ($shipments as $shipment) {
            foreach ($shipment as $data) {
                $amazonOrderItemId = (string)$data['amazon_order_item_id'];
                if (!isset($result[$amazonOrderItemId])) {
                    $result[$amazonOrderItemId] = 0;
                }

                $result[$amazonOrderItemId] += (int)$data['qty'];
            }
        }

        return $result;
    }
}
