<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Order;

class CreateMagentoOrder extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $force = $this->getRequest()->getParam('force');

        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->amazonFactory->getObjectLoaded('Order', (int)$id);
        $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

        // M2ePro_TRANSLATIONS
        // Magento Order is already created for this Amazon Order.
        if (!is_null($order->getMagentoOrderId()) && $force != 'yes') {
            $message = 'Magento Order is already created for this Amazon Order. ' .
                'Press Create Order Button to create new one.';

            $this->messageManager->addWarning(
                $this->__($message)
            );
            $this->_redirect('*/*/view', array('id' => $id));
            return;
        }

        // Create magento order
        // ---------------------------------------
        try {
            $order->createMagentoOrder();
            $this->messageManager->addSuccess($this->__('Magento Order was created.'));
        } catch (\Exception $e) {

            /**@var \Ess\M2ePro\Helper\Module\Exception $helper */
            $helper = $this->helperFactory->getObject('Module\Exception');
            $helper->process($e, false);

            $message = $this->__(
                'Magento Order was not created. Reason: %error_message%',
                $this->getHelper('Module\Log')->decodeDescription($e->getMessage())
            );
            $this->messageManager->addError($message);
        }
        // ---------------------------------------

        // Create invoice
        // ---------------------------------------
        if ($order->getChildObject()->canCreateInvoice()) {
            $result = $order->createInvoice();
            $result && $this->messageManager->addSuccess($this->__('Invoice was created.'));
        }
        // ---------------------------------------

        // Create shipment
        // ---------------------------------------
        if ($order->getChildObject()->canCreateShipment()) {
            $result = $order->createShipment();
            $result && $this->messageManager->addSuccess($this->__('Shipment was created.'));
        }
        // ---------------------------------------

        // ---------------------------------------
        $order->updateMagentoOrderStatus();
        // ---------------------------------------

        $this->_redirect('*/*/view', array('id' => $id));
    }
}