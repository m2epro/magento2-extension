<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer;

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
    )
    {
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

        $this->createChange($order);

        $order->getLog()->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);

        $result = $order->getChildObject()->updatePaymentStatus();

        $result ? $this->addSessionSuccessMessage()
                : $this->addSessionErrorMessage($order);
    }

    //########################################

    private function createChange(\Ess\M2ePro\Model\Order $order)
    {
        $orderId   = $order->getId();
        $action    = \Ess\M2ePro\Model\Order\Change::ACTION_UPDATE_PAYMENT;
        $creator   = \Ess\M2ePro\Model\Order\Change::CREATOR_TYPE_OBSERVER;
        $component = $order->getComponentMode();

        $this->activeRecordFactory->getObject('Order\Change')->create($orderId, $action, $creator, $component, array());
    }

    // ---------------------------------------

    private function addSessionSuccessMessage()
    {
        $message = $this->getHelper('Module\Translation')->__('Payment Status for eBay Order was updated to Paid.');
        $this->messageManager->addSuccess($message);
    }

    private function addSessionErrorMessage(\Ess\M2ePro\Model\Order $order)
    {
        $url = $this->urlBuilder->getUrl('*/ebay_log_order/index', array('id' => $order->getId()));

        $channelTitle = $order->getComponentTitle();
        // M2ePro\TRANSLATIONS
        // Payment Status for %channel_title% Order was not updated. View <a href="%url%" target="_blank">Order Log</a> for more details.
        $message  = $this->getHelper('Module\Translation')->__(
            'Payment Status for %channel_title% Order was not updated.'.
            ' View <a href="%url%" target="_blank">Order Log</a> for more details.',
            $channelTitle, $url
        );

        $this->messageManager->addError($message);
    }

    //########################################
}