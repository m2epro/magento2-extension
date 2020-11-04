<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order\Shipment\ItemToShipLoader;

use Ess\M2ePro\Helper\Data as Helper;
use Ess\M2ePro\Model\AbstractModel;
use Ess\M2ePro\Model\Order\Shipment\ItemToShipLoaderInterface;

/**
 * Class Ess\M2ePro\Model\Ebay\Order\Shipment\ItemToShipLoader\DefaultObject
 */
class DefaultObject extends AbstractModel implements ItemToShipLoaderInterface
{
    /** @var \Ess\M2ePro\Model\Order $order */
    protected $order;

    /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
    protected $shipmentItem;

    /** @var \Ess\M2ePro\Model\Order\Item $orderItem */
    protected $orderItem;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory */
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->ebayFactory = $ebayFactory;

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

        if (!$this->validate($additionalData)) {
            return [];
        }

        return [$this->shipmentItem->getOrderItem()->getId() => $this->getOrderItem($additionalData)];
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

        if (!isset($additionalData[Helper::CUSTOM_IDENTIFIER]['items'][0]['item_id'])) {
            return false;
        }

        if (!isset($additionalData[Helper::CUSTOM_IDENTIFIER]['items'][0]['transaction_id'])) {
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
        if ($this->orderItem !== null && $this->orderItem->getId()) {
            return $this->orderItem;
        }

        $this->orderItem = $this->ebayFactory->getObject('Order_Item')->getCollection()
            ->addFieldToFilter('order_id', $this->order->getId())
            ->addFieldToFilter('item_id', $additionalData[Helper::CUSTOM_IDENTIFIER]['items'][0]['item_id'])
            ->addFieldToFilter(
                'transaction_id',
                $additionalData[Helper::CUSTOM_IDENTIFIER]['items'][0]['transaction_id']
            )
            ->getFirstItem();

        return $this->orderItem;
    }

    //########################################
}
