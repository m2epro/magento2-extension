<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Shipment\ItemToShipLoader;

use Ess\M2ePro\Helper\Data as Helper;
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
        $additionalData = $this
            ->getHelper('Data')
            ->unserialize($this->shipmentItem->getOrderItem()->getAdditionalData());

        if ($cache = $this->getAlreadyProcessed($additionalData)) {
            return $cache;
        }

        $additionalDataItems = $additionalData[Helper::CUSTOM_IDENTIFIER]['items'] ?? [];

        $qtyShipped = (int)$this->shipmentItem->getQty();
        if ($qtyShipped <= 0) {
            return [];
        }

        $resultItems = [];
        $totalQtyPurchased = 0;
        foreach ($additionalDataItems as $additionalDataItem) {
            $orderItem = $this
                ->getOrderItemById((string)($additionalDataItem['order_item_id'] ?? ''));
            if ($orderItem === null) {
                continue;
            }

            $itemQty = $orderItem->getChildObject()->getQtyPurchased();
            $totalQtyPurchased += $itemQty;
            if ($totalQtyPurchased > $qtyShipped) {
                break;
            }

            $resultItems[] =  [
                'amazon_order_item_id' => $orderItem->getChildObject()->getAmazonOrderItemId(),
                'qty' => $itemQty,
            ];
        }

        if (empty($resultItems)) {
            return [];
        }

        $additionalData[Helper::CUSTOM_IDENTIFIER]['shipments'][$this->shipmentItem->getId()] = $resultItems;
        $this->saveAdditionalDataInShipmentItem($additionalData);

        return $resultItems;
    }

    // ----------------------------------------

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
     * @throws \Exception
     */
    protected function saveAdditionalDataInShipmentItem(array $additionalData)
    {
        $this->shipmentItem->getOrderItem()->setAdditionalData($this->getHelper('Data')->serialize($additionalData));
        $this->shipmentItem->getOrderItem()->save();
    }

    // ----------------------------------------

    private function getOrderItemById(string $orderItemId): ?\Ess\M2ePro\Model\Order\Item
    {
        if (empty($orderItemId)) {
            return null;
        }

        /** @var \Ess\M2ePro\Model\Order\Item $result */
        $result = $this->amazonFactory
            ->getObject('Order_Item')
            ->getCollection()
            ->addFieldToFilter('order_id', $this->order->getId())
            ->addFieldToFilter(
                'amazon_order_item_id',
                ['eq' => $orderItemId]
            )
            ->getFirstItem();

        if ($result->isObjectNew()) {
            return null;
        }

        return $result;
    }
}
