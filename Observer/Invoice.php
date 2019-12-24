<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer;

/**
 * Class \Ess\M2ePro\Observer\Invoice
 */
class Invoice extends AbstractModel
{
    protected $ebayFactory;
    protected $messageManager;
    protected $urlBuilder;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\Message\Manager $messageManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->ebayFactory = $ebayFactory;
        $this->messageManager = $messageManager;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        if ($this->getHelper('Data\GlobalData')->getValue('skip_invoice_observer')) {
            // Not process invoice observer when set such flag
            $this->getHelper('Data\GlobalData')->unsetValue('skip_invoice_observer');
            return;
        }

        /** @var $invoice \Magento\Sales\Model\Order\Invoice */
        $invoice = $this->getEvent()->getInvoice();
        $magentoOrderId = $invoice->getOrderId();

        try {
            /** @var $order \Ess\M2ePro\Model\Order */
            $order = $this->ebayFactory->getObjectLoaded('Order', $magentoOrderId, 'magento_order_id');
        } catch (\Exception $e) {
            return;
        }

        if (!$order->getChildObject()->canUpdatePaymentStatus()) {
            return;
        }

        $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
        $order->getChildObject()->updatePaymentStatus();
    }

    //########################################
}
