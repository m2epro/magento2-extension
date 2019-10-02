<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Shipment;

/**
 * This event was added for temporary fix in walmart integration,
 *  because sales_order_shipment_save_after providing ShipmentItem before it was saved, so it doesn't have ID.
 * TODO - make all integrations work with this event
 */
class Item extends \Ess\M2ePro\Observer\AbstractModel
{
    protected $messageManager;
    protected $urlBuilder;

    //########################################

    public function __construct(
        \Magento\Framework\Message\Manager $messageManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->messageManager = $messageManager;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        if ($this->getHelper('Data\GlobalData')->getValue('skip_shipment_observer')) {
            return;
        }

        /** @var $shipmentItem \Magento\Sales\Model\Order\Shipment\Item */
        $shipmentItem = $this->getEvent()->getShipmentItem();
        $shipment = $shipmentItem->getShipment();

        $magentoOrderId = $shipment->getOrderId();

        try {
            /** @var $order \Ess\M2ePro\Model\Order */
            $order = $this->activeRecordFactory->getObjectLoaded('Order', $magentoOrderId, 'magento_order_id');
        } catch (\Exception $e) {
            return;
        }

        if ($order === null) {
            return;
        }

        if (!in_array($order->getComponentMode(), $this->getHelper('Component')->getEnabledComponents())) {
            return;
        }

        /**
         * fix for walmart integration
         */
        if ($order->getComponentMode() != \Ess\M2ePro\Helper\Component\Walmart::NICK) {
            return;
        }

        $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

        /** @var $shipmentHandler \Ess\M2ePro\Model\Order\Shipment\Handler */
        $shipmentHandler = $this->modelFactory->getObject('Order_Shipment_Handler')
                                              ->factory($order->getComponentMode());
        $shipmentHandler->handle($order, $shipment);
    }

    //########################################
}
