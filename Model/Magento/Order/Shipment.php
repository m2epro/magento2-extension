<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Order;

class Shipment extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Framework\DB\TransactionFactory  */
    protected $transactionFactory = NULL;

    /** @var \Ess\M2ePro\Model\Magento\Order\Shipment\Factory|null  */
    protected $shipmentFactory    = NULL;

    /** @var $magentoOrder \Magento\Sales\Model\Order */
    private $magentoOrder         = NULL;

    /** @var $shipment \Magento\Sales\Model\Order\Shipment */
    private $shipment             = NULL;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Order\Shipment\Factory $shipmentFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory
    )
    {
        $this->shipmentFactory    = $shipmentFactory;
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

    //########################################

    public function getShipment()
    {
        return $this->shipment;
    }

    //########################################

    public function buildShipment()
    {
        $this->prepareShipment();
        $this->magentoOrder->getShipmentsCollection()->addItem($this->shipment);
    }

    //########################################

    protected function prepareShipment()
    {
        // Skip shipment observer
        // ---------------------------------------
        $this->getHelper('Data\GlobalData')->unsetValue('skip_shipment_observer');
        $this->getHelper('Data\GlobalData')->setValue('skip_shipment_observer', true);
        // ---------------------------------------

        $qtys = array();
        foreach ($this->magentoOrder->getAllItems() as $item) {
            $qtyToShip = $item->getQtyToShip();

            if ($qtyToShip == 0) {
                continue;
            }

            $qtys[$item->getId()] = $qtyToShip;
        }

        // Create shipment
        // ---------------------------------------
        $this->shipment = $this->shipmentFactory->create($this->magentoOrder);
        $this->shipment->register();
        // it is necessary for updating qty_shipped field in sales_flat_order_item table
        $this->shipment->getOrder()->setIsInProcess(true);

        $this->transactionFactory
             ->create()
             ->addObject($this->shipment)
             ->addObject($this->shipment->getOrder())
             ->save();
        // ---------------------------------------
    }

    //########################################
}