<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Order\ResendInvoiceCreditmemo
 */
class ResendInvoiceCreditmemo extends Order
{
    public function execute()
    {
        $ids = $this->getRequestIds();

        $hasFailed = false;
        $hasSucceeded = false;

        foreach ($ids as $id) {

            /** @var $order \Ess\M2ePro\Model\Order */
            $order = $this->amazonFactory->getObjectLoaded('Order', (int)$id);
            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

            if ($order->getChildObject()->sendCreditmemo()) {
                $hasSucceeded = true;
                continue;
            }

            if ($order->getChildObject()->sendInvoice()) {
                $hasSucceeded = true;
                continue;
            }

            $hasFailed = true;
        }

        if (!$hasFailed && $hasSucceeded) {
            $this->messageManager->addSuccess(
                $this->__('Selected Invoices or/and Credit Memos will be sent to Amazon.')
            );
        } elseif ($hasFailed && !$hasSucceeded) {
            $this->messageManager->addError(
                $this->__('Invoices or/and Credit Memos cannot be sent.')
            );
        } elseif ($hasFailed && $hasSucceeded) {
            $this->messageManager->addError(
                $this->__('Invoices or/and Credit Memos cannot be sent for some orders.')
            );
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
