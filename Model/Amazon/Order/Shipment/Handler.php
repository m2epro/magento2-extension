<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Shipment;

/**
 * Class Ess\M2ePro\Model\Amazon\Order\Shipment\Handler
 */
class Handler extends \Ess\M2ePro\Model\Order\Shipment\Handler
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    /**
     * @param \Ess\M2ePro\Model\Order $order
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    protected function getTrackingDetails(\Ess\M2ePro\Model\Order $order, \Magento\Sales\Model\Order\Shipment $shipment)
    {
        return array_merge(
            parent::getTrackingDetails($order, $shipment),
            ['fulfillment_date' => $shipment->getCreatedAt()]
        );
    }

    //########################################
}
