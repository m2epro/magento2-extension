<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order\View;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Order\View\Form
 */
class Form extends AbstractContainer
{
    protected $_template = 'amazon/order.phtml';

    protected $storeManager;

    public $shippingAddress = [];

    public $realMagentoOrderId = null;

    /** @var \Ess\M2ePro\Model\Order */
    public $order;

    //########################################

    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->storeManager = $storeManager;

        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonOrderViewForm');
        // ---------------------------------------

        $this->order = $this->getHelper('Data\GlobalData')->getValue('order');
    }

    protected function _beforeToHtml()
    {
        $this->realMagentoOrderId = null;

        $magentoOrder = $this->order->getMagentoOrder();
        if ($magentoOrder !== null) {
            $this->realMagentoOrderId = $magentoOrder->getRealOrderId();
        }

        $data = [
            'class'   => 'primary',
            'label'   => $this->__('Edit'),
            'onclick' => "OrderEditItemObj.openEditShippingAddressPopup({$this->order->getId()});",
        ];
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('edit_shipping_info', $buttonBlock);

        if ($magentoOrder !== null && $magentoOrder->hasShipments() && !$this->order->getChildObject()->isPrime()) {
            $url = $this->getUrl('*/order/resubmitShippingInfo', ['id' => $this->order->getId()]);
            $data = [
                'class'   => 'primary',
                'label'   => $this->__('Resend Shipping Information'),
                'onclick' => 'setLocation(\'' . $url . '\');',
            ];
            $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
            $this->setChild('resubmit_shipping_info', $buttonBlock);
        }

        if ($this->order->getChildObject()->canSendMagentoCreditmemo()) {
            $documentType = \Ess\M2ePro\Model\Amazon\Order\Invoice::DOCUMENT_TYPE_CREDIT_NOTE;
            $data = [
                'class'   => 'primary',
                'label'   => $this->__('Resend Credit Memo'),
                'onclick' => "AmazonOrderObj.resendInvoice({$this->order->getId()}, '{$documentType}');",
            ];
            $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
            $this->setChild('resend_document', $buttonBlock);
        } elseif ($this->order->getChildObject()->canSendMagentoInvoice()) {
            $documentType = \Ess\M2ePro\Model\Amazon\Order\Invoice::DOCUMENT_TYPE_INVOICE;
            $data = [
                'class'   => 'primary',
                'label'   => $this->__('Resend Invoice'),
                'onclick' => "AmazonOrderObj.resendInvoice({$this->order->getId()}, '{$documentType}');",
            ];
            $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
            $this->setChild('resend_document', $buttonBlock);
        }

        /** @var $shippingAddress \Ess\M2ePro\Model\Amazon\Order\ShippingAddress */
        $shippingAddress = $this->order->getShippingAddress();

        $this->shippingAddress = $shippingAddress->getData();
        $this->shippingAddress['country_name'] = $shippingAddress->getCountryName();

        if (!$this->order->getChildObject()->isCanceled()
            && !$this->order->getChildObject()->isPending()
            && !$this->order->getChildObject()->isFulfilledByAmazon()
            && $this->order->getMarketplace()->getChildObject()->isMerchantFulfillmentAvailable()
        ) {
            $data = [
                'class'   => 'primary',
                'label'   => $this->__('Use Amazon\'s Shipping Services'),
                'onclick' => "AmazonOrderMerchantFulfillmentObj.getPopupAction({$this->order->getId()});"
            ];
            $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
            $this->setChild('use_amazons_shipping_services', $buttonBlock);
        }

        $buttonAddNoteBlock = $this->createBlock('Magento\Button')
            ->setData(
                [
                    'label'   => $this->__('Add Note'),
                    'onclick' => "OrderNoteObj.openAddNotePopup({$this->order->getId()})",
                    'class'   => 'order_note_btn',
                ]
            );

        $this->jsUrl->addUrls(
            [
                'order/getDebugInformation'  => $this->getUrl(
                    '*/order/getDebugInformation/',
                    ['id' => $this->getRequest()->getParam('id')]
                ),
                'getEditShippingAddressForm' => $this->getUrl(
                    '*/amazon_order_shippingAddress/edit/',
                    ['id' => $this->getRequest()->getParam('id')]
                ),
                'saveShippingAddress'        => $this->getUrl(
                    '*/amazon_order_shippingAddress/save',
                    ['id' => $this->getRequest()->getParam('id')]
                ),
                'amazon_order/resendInvoice' => $this->getUrl(
                    '*/amazon_order/resendInvoice'
                ),
            ]
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Controller\Adminhtml\Order\EditItem::class)
        );

        $this->setChild('shipping_address', $this->createBlock('Amazon_Order_Edit_ShippingAddress'));
        $this->setChild('item', $this->createBlock('Amazon_Order_View_Item'));
        $this->setChild('item_edit', $this->createBlock('Order_Item_Edit'));
        $this->setChild('log', $this->createBlock('Order_View_Log_Grid'));
        $this->setChild('order_note_grid', $this->createBlock('Order_Note_Grid'));
        $this->setChild('add_note_button', $buttonAddNoteBlock);

        return parent::_beforeToHtml();
    }

    private function getStore()
    {
        if ($this->order->getData('store_id') === null) {
            return null;
        }

        try {
            $store = $this->storeManager->getStore($this->order->getData('store_id'));
        } catch (\Exception $e) {
            return null;
        }

        return $store;
    }

    public function isCurrencyAllowed()
    {
        $store = $this->getStore();

        if ($store === null) {
            return true;
        }

        return $this->modelFactory->getObject('Currency')->isAllowed(
            $this->order->getChildObject()->getCurrency(),
            $store
        );
    }

    public function hasCurrencyConversionRate()
    {
        $store = $this->getStore();

        if ($store === null) {
            return true;
        }

        return $this->modelFactory->getObject('Currency')->getConvertRateFromBase(
                $this->order->getChildObject()->getCurrency(),
                $store
            ) != 0;
    }

    public function formatPrice($currencyName, $priceValue)
    {
        return $this->modelFactory->getObject('Currency')->formatPrice($currencyName, $priceValue);
    }

    //########################################

    protected function _toHtml()
    {
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Order'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Order\MerchantFulfillment'));

        $orderNoteGridId = $this->getChildBlock('order_note_grid')->getId();
        $this->jsTranslator->add('Custom Note', $this->__('Custom Note'));

        $this->jsTranslator->addTranslations(
            [
                'View Full Order Log'                                  => $this->__('View Full Order Log'),
                'Amazon\'s Shipping Services'                          => $this->__('Amazon\'s Shipping Services'),
                'Please select an option.'                             => $this->__('Please select an option.'),
                'This is a required fields.'                           => $this->__('This is a required fields.'),
                'Please enter a number greater than 0 in this fields.' =>
                    $this->__('Please enter a number greater than 0 in this fields.'),
                'Are you sure you want to create Shipment now?'        =>
                    $this->__('Are you sure you want to create Shipment now?'),
                'Please enter a valid date.'                           => $this->__('Please enter a valid date.'),
            ]
        );

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Order/Note',
        'M2ePro/Amazon/Order',
        'M2ePro/Amazon/Order/MerchantFulfillment'
    ], function(){
        window.OrderNoteObj = new OrderNote('$orderNoteGridId');
        window.AmazonOrderObj = new AmazonOrder();
        window.AmazonOrderMerchantFulfillmentObj = new AmazonOrderMerchantFulfillment();
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################
}
