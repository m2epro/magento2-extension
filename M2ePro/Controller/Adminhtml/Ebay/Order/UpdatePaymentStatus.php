<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Order\UpdatePaymentStatus
 */
class UpdatePaymentStatus extends Order
{
    //########################################

    public function execute()
    {
        $ids = $this->getRequestIds();

        if (count($ids) == 0) {
            $this->messageManager->addError($this->__('Please select Order(s).'));
            return false;
        }

        $ordersCollection = $this->ebayFactory->getObject('Order')->getCollection();
        $ordersCollection->addFieldToFilter('id', ['in' => $ids]);

        $hasFailed = false;
        $hasSucceeded = false;

        foreach ($ordersCollection->getItems() as $order) {
            /** @var \Ess\M2ePro\Model\Order $order */
            $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
            $order->getChildObject()->updatePaymentStatus() ? $hasSucceeded = true
                                                            : $hasFailed = true;
        }

        if (!$hasFailed && $hasSucceeded) {
            $this->messageManager->addSuccess(
                $this->__('Updating eBay Order(s) Status to Paid in Progress...')
            );
        } elseif ($hasFailed && !$hasSucceeded) {
            $this->messageManager->addError(
                $this->__('eBay Order(s) can not be updated for Paid Status.')
            );
        } elseif ($hasFailed && $hasSucceeded) {
            $this->messageManager->addError(
                $this->__('Some of eBay Order(s) can not be updated for Paid Status.')
            );
        }

        return $this->_redirect($this->_redirect->getRefererUrl());
    }

    //########################################
}
