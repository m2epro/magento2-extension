<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order\Shipment;

use Ess\M2ePro\Helper\Data as Helper;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection as TrackCollection;

/**
 * Class Ess\M2ePro\Model\Order\Shipment\Handler
 */
abstract class Handler extends \Ess\M2ePro\Model\AbstractModel
{
    const HANDLE_RESULT_FAILED    = -1;
    const HANDLE_RESULT_SKIPPED   = 0;
    const HANDLE_RESULT_SUCCEEDED = 1;

    const CUSTOM_CARRIER_CODE = 'custom';

    protected $activeRecordFactory = null;
    protected $carrierFactory = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Shipping\Model\CarrierFactoryInterface $carrierFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->carrierFactory = $carrierFactory;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @return string
     */
    abstract protected function getComponentMode();

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment\Item $shipmentItem
     *
     * @return \Ess\M2ePro\Model\Order\Shipment\ItemToShipLoaderInterface
     */
    protected function getItemToShipLoader(
        \Ess\M2ePro\Model\Order $order,
        \Magento\Sales\Model\Order\Shipment\Item $shipmentItem
    ) {
        $additionalData = $this->getHelper('Data')->unserialize($shipmentItem->getOrderItem()->getAdditionalData());
        $data = isset($additionalData[Helper::CUSTOM_IDENTIFIER]) ? $additionalData[Helper::CUSTOM_IDENTIFIER] : [];

        $componentMode = ucfirst($this->getComponentMode());
        if (isset($data['pretended_to_be_simple']) && $data['pretended_to_be_simple'] === true) {
            return $this->modelFactory->getObject(
                "{$componentMode}_Order_Shipment_ItemToShipLoader_PretendedToBeSimple",
                ['data' => [$order, $shipmentItem]]
            );
        }

        return $this->modelFactory->getObject(
            "{$componentMode}_Order_Shipment_ItemToShipLoader_DefaultObject",
            ['data' => [$order, $shipmentItem]]
        );
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return int
     * @throws \Exception
     */
    public function handle(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $trackingDetails = $this->getTrackingDetails($order, $shipment);
        if (!$this->isNeedToHandle($order, $trackingDetails)) {
            return self::HANDLE_RESULT_SKIPPED;
        }

        $items = [];
        foreach ($shipment->getAllItems() as $shipmentItem) {
            /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
            $items = array_merge(
                $items,
                $this->getItemToShipLoader($order, $shipmentItem)->loadItem()
            );
        }

        return $this->processStatusUpdates($order, $trackingDetails, $items)
            ? self::HANDLE_RESULT_SUCCEEDED
            : self::HANDLE_RESULT_FAILED;
    }

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment\Item $shipmentItem
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function handleItem(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment\Item $shipmentItem)
    {
        $trackingDetails = $this->getTrackingDetails($order, $shipmentItem->getShipment());
        if (!$this->isNeedToHandle($order, $trackingDetails)) {
            return self::HANDLE_RESULT_SKIPPED;
        }

        $items = $this->getItemToShipLoader($order, $shipmentItem)->loadItem();
        return $this->processStatusUpdates($order, $trackingDetails, $items)
            ? self::HANDLE_RESULT_SUCCEEDED
            : self::HANDLE_RESULT_FAILED;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param array $trackingDetails
     * @param array $items
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function processStatusUpdates(\Ess\M2ePro\Model\Order $order, array $trackingDetails, array $items)
    {
        if (empty($items)) {
            return false;
        }

        return $order->getChildObject()->updateShippingStatus($trackingDetails, $items);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    protected function getTrackingDetails(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $tracks = $shipment->getTracks();
        empty($tracks) && $tracks = $shipment->getTracksCollection();

        /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
        $track = $tracks instanceof TrackCollection ? $tracks->getLastItem() : end($tracks);
        $number = trim((string)$track->getData('track_number'));
        if (empty($number)) {
            return [];
        }

        $carrierCode = $carrierTitle = trim($track->getData('carrier_code'));
        $carrier = $this->carrierFactory->create($carrierCode, $order->getStoreId());
        $carrier && $carrierTitle = $carrier->getConfigData('title');

        return [
            'carrier_code'    => $carrierCode,
            'carrier_title'   => $carrierTitle,
            'shipping_method' => trim($track->getData('title')),
            'tracking_number' => $number
        ];
    }

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param array $trackingDetails
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function isNeedToHandle(\Ess\M2ePro\Model\Order $order, array $trackingDetails)
    {
        if ($order->getComponentMode() !== $this->getComponentMode()) {
            throw new \InvalidArgumentException('Invalid component mode.');
        }

        return $order->getChildObject()->canUpdateShippingStatus($trackingDetails);
    }

    //########################################
}
