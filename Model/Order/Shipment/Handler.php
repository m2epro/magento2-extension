<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

/**
 * Handles shipments, created by seller in admin panel
 */
namespace Ess\M2ePro\Model\Order\Shipment;

class Handler extends \Ess\M2ePro\Model\AbstractModel
{
    const HANDLE_RESULT_FAILED    = -1;
    const HANDLE_RESULT_SKIPPED   = 0;
    const HANDLE_RESULT_SUCCEEDED = 1;

    //########################################

    public function factory($component)
    {
        $handler = null;

        switch ($component) {
            case\Ess\M2ePro\Helper\Component\Amazon::NICK:
                $handler = $this->modelFactory->getObject('Amazon\Order\Shipment\Handler');
                break;
            case\Ess\M2ePro\Helper\Component\Ebay::NICK:
                $handler = $this->modelFactory->getObject('Ebay\Order\Shipment\Handler');
                break;
        }

        if (!$handler) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Shipment handler not found.');
        }

        return $handler;
    }

    public function handle(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        $trackingDetails = $this->getTrackingDetails($shipment);

        if (!$order->getChildObject()->canUpdateShippingStatus($trackingDetails)) {
            return self::HANDLE_RESULT_SKIPPED;
        }

        return $order->getChildObject()->updateShippingStatus($trackingDetails)
            ? self::HANDLE_RESULT_SUCCEEDED
            : self::HANDLE_RESULT_FAILED;
    }

    protected function getTrackingDetails(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $track = $shipment->getTracksCollection()->getLastItem();
        $trackingDetails = array();

        $number = trim($track->getData('number'));

        if (!empty($number)) {
            $carrierCode = trim($track->getData('carrier_code'));

            if (strtolower($carrierCode) == 'dhlint') {
                $carrierCode = 'dhl';
            }

            $trackingDetails = array(
                'carrier_title'   => trim($track->getData('title')),
                'carrier_code'    => $carrierCode,
                'tracking_number' => (string)$number
            );
        }

        return $trackingDetails;
    }

    //########################################
}