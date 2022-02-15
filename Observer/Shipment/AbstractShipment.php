<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Shipment;

abstract class AbstractShipment extends \Ess\M2ePro\Observer\AbstractModel
{
    protected $orderFactory;
    protected $orderResource;
    protected $shipmentCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ResourceModel\Order $orderResource,
        \Ess\M2ePro\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory $shipmentCollectionFactory
    ) {
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);

        $this->orderFactory              = $orderFactory;
        $this->orderResource             = $orderResource;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
    }

    //########################################

    /**
     * @param \Magento\Sales\Model\Order\Shipment\Item|\Magento\Sales\Model\Order\Shipment\Track $source
     * @return \Magento\Sales\Model\Order\Shipment|null
     */
    protected function getShipment($source)
    {
        $shipment = $source->getShipment();
        if ($shipment != null && $shipment->getId()) {
            return $shipment;
        }

        $shipmentCollection = $this->shipmentCollectionFactory->create()
            ->addFieldToFilter('entity_id', $source->getParentId());

        /** @var $shipment \Magento\Sales\Model\Order\Shipment */
        $shipment = $shipmentCollection->getFirstItem();
        if ($shipment != null && $shipment->getId()) {
            return $shipment;
        }

        return null;
    }
}
