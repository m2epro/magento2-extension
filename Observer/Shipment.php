<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer;

class Shipment extends AbstractModel
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
    )
    {
        $this->messageManager = $messageManager;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        if ($this->getHelper('Data\GlobalData')->getValue('skip_shipment_observer')) {
            $this->getHelper('Data\GlobalData')->unsetValue('skip_shipment_observer');
            return;
        }

        /** @var $shipment \Magento\Sales\Model\Order\Shipment */
        $shipment = $this->getEvent()->getShipment();

        $magentoOrderId = $shipment->getOrderId();

        try {
            /** @var $order \Ess\M2ePro\Model\Order */
            $order = $this->activeRecordFactory->getObjectLoaded('Order', $magentoOrderId, 'magento_order_id');
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

        /** @var $shipmentHandler \Ess\M2ePro\Model\Order\Shipment\Handler */
        $shipmentHandler = $this->modelFactory->getObject('Order\Shipment\Handler')
                                              ->factory($order->getComponentMode());
        $shipmentHandler->handle($order, $shipment);
    }

    //########################################
}