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
        // Magento order data
        // ---------------------------------------
        $this->realMagentoOrderId = null;

        $magentoOrder = $this->order->getMagentoOrder();
        if ($magentoOrder !== null) {
            $this->realMagentoOrderId = $magentoOrder->getRealOrderId();
        }
        // ---------------------------------------

        $data = [
            'class' => 'primary',
            'label'   => $this->__('Edit'),
            'onclick' => "OrderEditItemObj.openEditShippingAddressPopup({$this->order->getId()});",
        ];
        $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
        $this->setChild('edit_shipping_info', $buttonBlock);

        // ---------------------------------------
        if ($magentoOrder !== null && $magentoOrder->hasShipments() && !$this->order->getChildObject()->isPrime()) {
            $url = $this->getUrl('*/order/resubmitShippingInfo', ['id' => $this->order->getId()]);
            $data = [
                'class'   => 'primary',
                'label'   => $this->__('Resend Shipping Information'),
                'onclick' => 'setLocation(\''.$url.'\');',
            ];
            $buttonBlock = $this->createBlock('Magento\Button')->setData($data);
            $this->setChild('resubmit_shipping_info', $buttonBlock);
        }
        // ---------------------------------------

        // Shipping data
        // ---------------------------------------
        /** @var $shippingAddress \Ess\M2ePro\Model\Amazon\Order\ShippingAddress */
        $shippingAddress = $this->order->getShippingAddress();

        $this->shippingAddress = $shippingAddress->getData();
        $this->shippingAddress['country_name'] = $shippingAddress->getCountryName();
        // ---------------------------------------

        $this->jsUrl->addUrls([
            'order/getDebugInformation' => $this->getUrl(
                '*/order/getDebugInformation/',
                ['id' => $this->getRequest()->getParam('id')]
            ),
            'getEditShippingAddressForm' => $this->getUrl(
                '*/amazon_order_shippingAddress/edit/',
                ['id' => $this->getRequest()->getParam('id')]
            ),
            'saveShippingAddress' => $this->getUrl(
                '*/amazon_order_shippingAddress/save',
                ['id' => $this->getRequest()->getParam('id')]
            ),
        ]);

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Controller\Adminhtml\Order\EditItem::class)
        );

        $this->setChild('shipping_address', $this->createBlock('Amazon_Order_Edit_ShippingAddress'));
        $this->setChild('item', $this->createBlock('Amazon_Order_View_Item'));
        $this->setChild('item_edit', $this->createBlock('Order_Item_Edit'));
        $this->setChild('log', $this->createBlock('Order_View_Log_Grid'));

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
}
