<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Order;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Order;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Order\CreateMagentoOrder
 */
class CreateMagentoOrder extends Order
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $force = $this->getRequest()->getParam('force');

        /** @var $order \Ess\M2ePro\Model\Order */
        $order = $this->walmartFactory->getObjectLoaded('Order', (int)$id);
        $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);

        // M2ePro_TRANSLATIONS
        // Magento Order is already created for this Walmart Order.
        if ($order->getMagentoOrderId() !== null && $force != 'yes') {
            $message = 'Magento Order is already created for this Walmart Order. ' .
                'Press Create Order Button to create new one.';

            $this->messageManager->addWarning(
                $this->__($message)
            );
            $this->_redirect('*/*/view', ['id' => $id]);
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
        if ($order->getChildObject()->canCreateShipments()) {
            $result = $order->createShipments();
            $result && $this->messageManager->addSuccess($this->__('Shipment was created.'));
        }
        // ---------------------------------------

        // ---------------------------------------
        $order->updateMagentoOrderStatus();
        // ---------------------------------------

        $this->_redirect('*/*/view', ['id' => $id]);
    }
}
