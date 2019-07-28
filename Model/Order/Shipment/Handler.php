<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * Handles shipments, created by seller in admin panel
 */
namespace Ess\M2ePro\Model\Order\Shipment;

use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection as TrackCollection;

class Handler extends \Ess\M2ePro\Model\AbstractModel
{
    const HANDLE_RESULT_FAILED    = -1;
    const HANDLE_RESULT_SKIPPED   = 0;
    const HANDLE_RESULT_SUCCEEDED = 1;

    const CUSTOM_CARRIER_CODE = 'custom';

    protected $activeRecordFactory = NULL;
    protected $carrierFactory = NULL;

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

    public function factory($component)
    {
        $handler = null;

        switch ($component) {
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                $handler = $this->modelFactory->getObject('Amazon\Order\Shipment\Handler');
                break;
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                $handler = $this->modelFactory->getObject('Ebay\Order\Shipment\Handler');
                break;
            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                $handler = $this->modelFactory->getObject('Walmart\Order\Shipment\Handler');
                break;
        }

        if (!$handler) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Shipment handler not found.');
        }

        return $handler;
    }

    public function handle(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $trackingDetails = $this->getTrackingDetails($order, $shipment);

        if (!$order->getChildObject()->canUpdateShippingStatus($trackingDetails)) {
            return self::HANDLE_RESULT_SKIPPED;
        }

        return $order->getChildObject()->updateShippingStatus($trackingDetails)
            ? self::HANDLE_RESULT_SUCCEEDED
            : self::HANDLE_RESULT_FAILED;
    }

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    protected function getTrackingDetails(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $track = null;
        $hasTracks = false;
        if ($shipment->hasData(ShipmentInterface::TRACKS) &&
            is_array($shipment->getData(ShipmentInterface::TRACKS))) {
            $tracks = $shipment->getData(ShipmentInterface::TRACKS);
            foreach ($tracks as $track) {
                if ($track instanceof \Magento\Sales\Model\Order\Shipment\Track) {
                    $hasTracks = true;
                }
            }
        }
        if (!$hasTracks) {
            $track = $shipment
                ->getTracksCollection()
                ->addFieldToFilter('order_id', $order->getMagentoOrderId())
                ->getLastItem();
        }
        $trackingDetails = [];
        $number = trim($track->getData('track_number'));
        if ($track && !empty($number)) {
            $carrierCode = $carrierTitle = trim($track->getData('carrier_code'));
            $carrier = $this->carrierFactory->create($carrierCode, $order->getStoreId());
            $carrier && $carrierTitle = $carrier->getConfigData('title');
            $trackingDetails = [
                'carrier_code' => $carrierCode,
                'carrier_title' => $carrierTitle,
                'shipping_method' => trim($track->getData('title')),
                'tracking_number' => (string)$number
            ];
        }
        return $trackingDetails;
    }

    //########################################
}