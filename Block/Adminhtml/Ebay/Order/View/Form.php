<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Order\View;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

class Form extends AbstractContainer
{
    protected $_template = 'ebay/order.phtml';

    protected $storeManager;

    protected $taxCalculator;

    public $shippingAddress = array();

    public $ebayWarehouseAddress = array();

    public $globalShippingServiceDetails = array();

    public $realMagentoOrderId = null;

    /** @var $order \Ess\M2ePro\Model\Order */
    public $order = null;

    //########################################

    public function __construct(
        \Magento\Tax\Model\Calculation $taxCalculator,
        \Magento\Store\Model\StoreManager $storeManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    )
    {
        $this->taxCalculator = $taxCalculator;
        $this->storeManager = $storeManager;

        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayOrderViewForm');
        // ---------------------------------------

        $this->order = $this->getHelper('Data\GlobalData')->getValue('order');
    }

    //########################################

    protected function _beforeToHtml()
    {
        // Magento order data
        // ---------------------------------------
        $this->realMagentoOrderId = NULL;

        $magentoOrder = $this->order->getMagentoOrder();
        if (!is_null($magentoOrder)) {
            $this->realMagentoOrderId = $magentoOrder->getRealOrderId();
        }
        // ---------------------------------------

        $data = array(
            'class' => 'primary',
            'label'   => $this->__('Edit'),
            'onclick' => "OrderEditItemObj.openEditShippingAddressPopup({$this->order->getId()});",
        );
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('edit_shipping_info', $buttonBlock);

        // ---------------------------------------
        if (!is_null($magentoOrder) && $magentoOrder->hasShipments()) {
            $url = $this->getUrl('*/order/resubmitShippingInfo', array('id' => $this->order->getId()));
            $data = array(
                'label'   => $this->__('Resend Shipping Information'),
                'onclick' => 'setLocation(\''.$url.'\');',
            );
            $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
            $this->setChild('resubmit_shipping_info', $buttonBlock);
        }
        // ---------------------------------------

        // Shipping data
        // ---------------------------------------
        /** @var $shippingAddress \Ess\M2ePro\Model\Ebay\Order\ShippingAddress */
        $shippingAddress = $this->order->getShippingAddress();

        $this->shippingAddress = $shippingAddress->getData();
        $this->shippingAddress['country_name'] = $shippingAddress->getCountryName();
        // ---------------------------------------

        // Global Shipping data
        // ---------------------------------------
        $globalShippingDetails = $this->order->getChildObject()->getGlobalShippingDetails();
        if (!empty($globalShippingDetails)) {
            $this->ebayWarehouseAddress = $globalShippingDetails['warehouse_address'];
            $this->globalShippingServiceDetails = $globalShippingDetails['service_details'];
        }
        // ---------------------------------------

        $this->setChild('shipping_address', $this->createBlock('Ebay\Order\Edit\ShippingAddress'));
        $this->setChild('item', $this->createBlock('Ebay\Order\View\Item'));
        $this->setChild('item_edit', $this->createBlock('Order\Item\Edit'));
        $this->setChild('log', $this->createBlock('Order\View\Log\Grid'));
        $this->setChild('external_transaction', $this->createBlock('Ebay\Order\View\ExternalTransaction'));

        $this->jsUrl->addUrls([
            'order/getDebugInformation' => $this->getUrl(
                '*/order/getDebugInformation/', array('id' => $this->getRequest()->getParam('id'))
            ),
            'getEditShippingAddressForm' => $this->getUrl(
                '*/ebay_order_shippingAddress/edit', array('id' => $this->getRequest()->getParam('id'))
            ),
            'saveShippingAddress' => $this->getUrl(
                '*/ebay_order_shippingAddress/save', array('id' => $this->getRequest()->getParam('id'))
            ),
        ]);

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Controller\Adminhtml\Order\EditItem')
        );

        return parent::_beforeToHtml();
    }

    //########################################

    private function getStore()
    {
        if (is_null($this->order->getData('store_id'))) {
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

        if (is_null($store)) {
            return true;
        }

        /** @var $currencyHelper \Ess\M2ePro\Model\Currency */
        $currencyHelper = $this->modelFactory->getObject('Currency');

        return $currencyHelper->isAllowed($this->order->getChildObject()->getCurrency(), $store);
    }

    public function hasCurrencyConversionRate()
    {
        $store = $this->getStore();

        if (is_null($store)) {
            return true;
        }

        /** @var $currencyHelper \Ess\M2ePro\Model\Currency */
        $currencyHelper = $this->modelFactory->getObject('Currency');

        return $currencyHelper->getConvertRateFromBase($this->order->getChildObject()->getCurrency(), $store) != 0;
    }

    //########################################

    public function getSubtotalPrice()
    {
        return $this->order->getChildObject()->getSubtotalPrice();
    }

    public function getShippingPrice()
    {
        $shippingPrice = $this->order->getChildObject()->getShippingPrice();
        if (!$this->order->getChildObject()->isVatTax()) {
            return $shippingPrice;
        }

        $shippingPrice -= $this->taxCalculator->calcTaxAmount(
            $shippingPrice, $this->order->getChildObject()->getTaxRate(), true, false
        );

        return $shippingPrice;
    }

    public function getTaxAmount()
    {
        $taxAmount = $this->order->getChildObject()->getTaxAmount();
        if (!$this->order->getChildObject()->isVatTax()) {
            return $taxAmount;
        }

        $shippingPrice = $this->order->getChildObject()->getShippingPrice();
        $shippingTaxAmount = $this->taxCalculator->calcTaxAmount(
            $shippingPrice, $this->order->getChildObject()->getTaxRate(), true, false
        );

        return $taxAmount + $shippingTaxAmount;
    }

    public function formatPrice($currencyName, $priceValue)
    {
        return $this->modelFactory->getObject('Currency')->formatPrice($currencyName, $priceValue);
    }

    //########################################
}