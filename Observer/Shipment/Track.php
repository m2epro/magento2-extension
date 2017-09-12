<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Shipment;

class Track extends \Ess\M2ePro\Observer\AbstractModel
{
    //########################################

    public function process()
    {
        try {

            if ($this->getHelper('Data\GlobalData')->getValue('skip_shipment_observer')) {
                return;
            }

            /** @var $track \Magento\Sales\Model\Order\Shipment\Track */

            $track = $this->getEvent()->getTrack();

            $shipment = $track->getShipment();

            $magentoOrderId = $shipment->getOrderId();

            try {
                /** @var $order \Ess\M2ePro\Model\Order */
                $order = $this->activeRecordFactory->getObjectLoaded(
                    'Order', $magentoOrderId, 'magento_order_id'
                );
            } catch (\Exception $e) {
                return;
            }

            if (is_null($order)) {
                return;
            }

            if (!in_array($order->getComponentMode(), $this->getHelper('Component')->getEnabledComponents())) {
                return;
            }

            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

            // ---------------------------------------

            /** @var $shipmentHandler \Ess\M2ePro\Model\Order\Shipment\Handler */
            $shipmentHandler = $this->modelFactory
                                    ->getObject('Order\Shipment\Handler')
                                    ->factory($order->getComponentMode());
            $shipmentHandler->handle($order, $shipment);

        } catch (\Exception $exception) {

            $this->getHelper('Module\Exception')->process($exception);

        }
    }

    //########################################
}