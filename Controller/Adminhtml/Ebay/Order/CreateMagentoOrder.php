<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Order;

class CreateMagentoOrder extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $force = $this->getRequest()->getParam('force');

        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->ebayFactory->getObjectLoaded('Order', (int)$id);
        $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

        if (!is_null($order->getMagentoOrderId()) && $force != 'yes') {
            // M2ePro_TRANSLATIONS
            // Magento Order is already created for this eBay Order. Press Create Order Button to create new one.
            $message = 'Magento Order is already created for this eBay Order. ' .
                'Press Create Order Button to create new one.';

            $this->messageManager->addWarning(
                $this->__($message)
            );
            return $this->_redirect('*/*/view', array('id' => $id));
        }

        // Create magento order
        // ---------------------------------------
        try {
            $order->createMagentoOrder();
            $this->messageManager->addSuccess($this->__('Magento Order was created.'));
        } catch (\Exception $e) {
            $message = $this->__(
                'Magento Order was not created. Reason: %error_message%',
                $this->activeRecordFactory->getObject('Log\AbstractLog')->decodeDescription($e->getMessage())
            );
            $this->messageManager->addError($message);
        }
        // ---------------------------------------

        if ($order->getChildObject()->canCreatePaymentTransaction()) {
            $order->getChildObject()->createPaymentTransactions();
        }

        if ($order->getChildObject()->canCreateInvoice()) {
            $result = $order->createInvoice();
            $result && $this->messageManager->addSuccess($this->__('Invoice was created.'));
        }

        if ($order->getChildObject()->canCreateShipment()) {
            $result = $order->createShipment();
            $result && $this->messageManager->addSuccess($this->__('Shipment was created.'));
        }

        if ($order->getChildObject()->canCreateTracks()) {
            $order->getChildObject()->createTracks();
        }

        // ---------------------------------------
        $order->updateMagentoOrderStatus();
        // ---------------------------------------

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}