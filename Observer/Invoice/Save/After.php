<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Invoice\Save;

/**
 * Class Ess\M2ePro\Observer\Invoice\Save\After
 */
class After extends \Ess\M2ePro\Observer\AbstractModel
{
    //########################################

    public function process()
    {
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $this->getEvent()->getInvoice();
        $magentoOrderId = $invoice->getOrderId();

        try {
            /** @var $order \Ess\M2ePro\Model\Order */
            $order = $this->activeRecordFactory->getObjectLoaded('Order', $magentoOrderId, 'magento_order_id');
        } catch (\Exception $e) {
            return;
        }

        if ($order->isComponentModeAmazon()) {
            $order->getChildObject()->sendInvoice();
        }
    }

    //########################################
}
