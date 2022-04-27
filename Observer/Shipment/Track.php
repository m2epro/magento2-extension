<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Shipment;

/**
 * Class \Ess\M2ePro\Observer\Shipment\Track
 */
class Track extends \Ess\M2ePro\Observer\Shipment\AbstractShipment
{
    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process()
    {
        if ($this->getHelper('Data\GlobalData')->getValue('skip_shipment_observer')) {
            return;
        }

        /** @var $track \Magento\Sales\Model\Order\Shipment\Track */

        $track = $this->getEvent()->getTrack();

        $shipment = $this->getShipment($track);

        if (!$shipment) {
            $class = get_class($this);
            $this->getHelper('Module\Logger')->process(
                [],
                "M2ePro observer $class cannot get shipment data from event or database"
            );

            return;
        }

        /**
         * Due to task m2e-team/m2e-pro/backlog#3421 this event observer can be called two times.
         * If first time was successful, second time will be skipped.
         * "Successful" means "$shipment variable is not null".
         * There is code that looks same below, but event keys and logic are different.
         */
        $eventKey = 'skip_shipment_track_' . $track->getId();
        if ($this->getHelper('Data_GlobalData')->getValue($eventKey)) {
            return;
        }

        $this->getHelper('Data_GlobalData')->setValue($eventKey, true);

        $magentoOrderId = $shipment->getOrderId();

        /**
         * We can catch two the same events: save of \Magento\Sales\Model\Order\Shipment\Item and
         * \Magento\Sales\Model\Order\Shipment\Track. So we must skip a duplicated one.
         * Possible situations:
         * 1. Shipment without tracks was created for Magento order. Only 'Item' observer will be called.
         * 2. Shipment with track(s) was created for Magento order. Both 'Item' and 'Track' observers will be called.
         * 3. New track(s) was added for existing shipment. Only 'Track' observer will be called.
         */
        $eventKey = 'skip_' . $shipment->getId() . '##' . spl_object_hash($track);
        if ($this->getHelper('Data_GlobalData')->getValue($eventKey)) {
            $this->getHelper('Data_GlobalData')->unsetValue($eventKey);
            return;
        }

        try {
            /** @var $order \Ess\M2ePro\Model\Order */
            $order = $this->orderFactory->create();
            $this->orderResource->load($order, $magentoOrderId, 'magento_order_id');
        } catch (\Exception $e) {
            return;
        }

        if ($order->isEmpty()) {
            return;
        }

        if (!in_array($order->getComponentMode(), $this->getHelper('Component')->getEnabledComponents())) {
            return;
        }

        $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

        // ---------------------------------------

        /** @var $shipmentHandler \Ess\M2ePro\Model\Order\Shipment\Handler */
        $componentMode = ucfirst($order->getComponentMode());
        $shipmentHandler = $this->modelFactory->getObject("{$componentMode}_Order_Shipment_Handler");
        $shipmentHandler->handle($order, $shipment);
    }

    //########################################
}
