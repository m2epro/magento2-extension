<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Order;

/**
 * Class \Ess\M2ePro\Model\Magento\Order\Shipment
 */
class Shipment extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Sales\Model\Order */
    protected $magentoOrder;

    /** @var \Magento\Sales\Model\Order\Shipment[] */
    protected $shipments = [];

    // ---------------------------------------

    /** @var \Magento\Framework\DB\TransactionFactory  */
    protected $transactionFactory;

    /** @var \Ess\M2ePro\Model\Magento\Order\Shipment\DocumentFactory  */
    protected $shipmentDocumentFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Order\Shipment\DocumentFactory $shipmentDocumentFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory
    ) {
        $this->shipmentDocumentFactory = $shipmentDocumentFactory;
        $this->transactionFactory = $transactionFactory;

        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param \Magento\Sales\Model\Order $magentoOrder
     * @return $this
     */
    public function setMagentoOrder(\Magento\Sales\Model\Order $magentoOrder)
    {
        $this->magentoOrder = $magentoOrder;
        return $this;
    }

    public function getShipments()
    {
        return $this->shipments;
    }

    //########################################

    public function buildShipments()
    {
        $this->prepareShipments();

        $this->getHelper('Data\GlobalData')->unsetValue('skip_shipment_observer');
        $this->getHelper('Data\GlobalData')->setValue('skip_shipment_observer', true);

        /** @var \Magento\Framework\DB\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        foreach ($this->shipments as $shipment) {

            // it is necessary for updating qty_shipped field in sales_flat_order_item table
            $shipment->getOrder()->setIsInProcess(true);

            $transaction->addObject($shipment);
            $transaction->addObject($shipment->getOrder());

            $this->magentoOrder->getShipmentsCollection()->addItem($shipment);
        }

        try {
            $transaction->save();
        } catch (\Exception $e) {
            $this->magentoOrder->getShipmentsCollection()->clear();
            throw $e;
        }

        $this->getHelper('Data\GlobalData')->unsetValue('skip_shipment_observer');
    }

    //########################################

    protected function prepareShipments()
    {
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        $shipment = $this->shipmentDocumentFactory->create($this->magentoOrder);
        $shipment->register();

        $this->shipments[] = $shipment;
    }

    //########################################
}
