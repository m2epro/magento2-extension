<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Order;

/**
 * Class \Ess\M2ePro\Model\Magento\Order\Invoice
 */
class Invoice extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Framework\DB\TransactionFactory  */
    protected $transactionFactory = null;

    /** @var $magentoOrder \Magento\Sales\Model\Order */
    protected $magentoOrder       = null;

    /** @var $invoice \Magento\Sales\Model\Order\Invoice */
    protected $invoice            = null;

    //########################################

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
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

    public function getInvoice()
    {
        return $this->invoice;
    }

    //########################################

    public function buildInvoice()
    {
        $this->prepareInvoice();
    }

    //########################################

    private function prepareInvoice()
    {
        // Skip invoice observer
        // ---------------------------------------
        $this->getHelper('Data\GlobalData')->unsetValue('skip_invoice_observer');
        $this->getHelper('Data\GlobalData')->setValue('skip_invoice_observer', true);
        // ---------------------------------------

        $qtys = [];
        foreach ($this->magentoOrder->getAllItems() as $item) {
            $qtyToInvoice = $item->getQtyToInvoice();

            if ($qtyToInvoice == 0) {
                continue;
            }

            $qtys[$item->getId()] = $item->getQtyToInvoice();
        }

        // Create invoice
        // ---------------------------------------
        $this->invoice = $this->magentoOrder->prepareInvoice($qtys);
        $this->invoice->register();
        // it is necessary for updating qty_invoiced field in sales_flat_order_item table
        $this->invoice->getOrder()->setIsInProcess(true);

        $this->transactionFactory
             ->create()
             ->addObject($this->invoice)
             ->addObject($this->invoice->getOrder())
             ->save();
        // ---------------------------------------
    }

    //########################################
}
