<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Order\View;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

class Form extends AbstractContainer
{
    protected $_template = 'ebay/order.phtml';

    protected $storeManager;

    protected $taxCalculator;

    public $shippingAddress = [];

    public $ebayWarehouseAddress = [];

    public $globalShippingServiceDetails = [];

    public $realMagentoOrderId;

    /** @var \Ess\M2ePro\Model\Order */
    public $order;

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Magento\Tax\Model\Calculation $taxCalculator,
        \Magento\Store\Model\StoreManager $storeManager,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->taxCalculator = $taxCalculator;
        $this->storeManager = $storeManager;
        $this->supportHelper = $supportHelper;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;

        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayOrderViewForm');

        $this->order = $this->globalDataHelper->getValue('order');
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
        $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                                         ->setData($data);
        $this->setChild('edit_shipping_info', $buttonBlock);

        // ---------------------------------------
        if ($magentoOrder !== null && $magentoOrder->hasShipments()) {
            $url = $this->getUrl('*/order/resubmitShippingInfo', ['id' => $this->order->getId()]);
            $data = [
                'class'   => 'primary',
                'label'   => $this->__('Resend Shipping Information'),
                'onclick' => 'setLocation(\''.$url.'\');',
            ];
            $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                                             ->setData($data);
            $this->setChild('resubmit_shipping_info', $buttonBlock);
        }
        // ---------------------------------------

        // Shipping data
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Ebay\Order\ShippingAddress $shippingAddress */
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
        $buttonAddNoteBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
            ->setData(
                [
                    'label'   => $this->__('Add Note'),
                    'onclick' => "OrderNoteObj.openAddNotePopup({$this->order->getId()})",
                    'class'   => 'order_note_btn',
                ]
            );

        $this->setChild('shipping_address',
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Order\Edit\ShippingAddress::class)
        );
        $this->setChild('item',
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Order\View\Item::class)
        );
        $this->setChild('item_edit',
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\Item\Edit::class)
        );
        $this->setChild('log',
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\View\Log\Grid::class)
        );
        $this->setChild('order_note_grid',
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Order\Note\Grid::class)
        );
        $this->setChild('add_note_button', $buttonAddNoteBlock);
        $this->setChild('external_transaction',
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Order\View\ExternalTransaction::class)
        );

        $this->jsUrl->addUrls([
            'order/actualizeFee' => $this->getUrl(
                '*/ebay_order_finalFee/update/',
                ['id' => $this->getRequest()->getParam('id')]
            ),
            'order/getDebugInformation' => $this->getUrl(
                '*/order/getDebugInformation/',
                ['id' => $this->getRequest()->getParam('id')]
            ),
            'getEditShippingAddressForm' => $this->getUrl(
                '*/ebay_order_shippingAddress/edit',
                ['id' => $this->getRequest()->getParam('id')]
            ),
            'saveShippingAddress' => $this->getUrl(
                '*/ebay_order_shippingAddress/save',
                ['id' => $this->getRequest()->getParam('id')]
            ),
            'ebay_account/edit' => $this->getUrl(
                '*/ebay_account/edit',
                [
                    'close_on_save' => true,
                ]
            ),
        ]);

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Controller\Adminhtml\Order\EditItem::class)
        );

        return parent::_beforeToHtml();
    }

    //########################################

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

        /** @var \Ess\M2ePro\Model\Currency $currencyHelper */
        $currencyHelper = $this->modelFactory->getObject('Currency');

        return $currencyHelper->isAllowed($this->order->getChildObject()->getCurrency(), $store);
    }

    public function hasCurrencyConversionRate()
    {
        $store = $this->getStore();

        if ($store === null) {
            return true;
        }

        /** @var \Ess\M2ePro\Model\Currency $currencyHelper */
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
            $shippingPrice,
            $this->order->getChildObject()->getTaxRate(),
            true,
            false
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
            $shippingPrice,
            $this->order->getChildObject()->getTaxRate(),
            true,
            false
        );

        return $taxAmount + $shippingTaxAmount;
    }

    public function formatPrice($currencyName, $priceValue)
    {
        return $this->modelFactory->getObject('Currency')->formatPrice($currencyName, $priceValue);
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getStatus()
    {
        if ($this->order->getChildObject()->isCanceled()) {
            $status = [
                'value' => $this->__('Canceled'),
                'color' => 'red'
            ];
        } elseif ($this->order->getChildObject()->isShippingCompleted()) {
            $status = [
                'value' => $this->__('Shipped'),
                'color' => 'green'
            ];
        } elseif ($this->order->getChildObject()->isPaymentCompleted()) {
            $status = [
                'value' => $this->__('Unshipped'),
                'color' => 'black'
            ];
        } else {
            $status = [
                'value' => $this->__('Pending'),
                'color' => 'gray'
            ];
        }

        return $status;
    }

    //########################################

    public function canUpdateFee(): bool
    {
        return $this->order->getChildObject()->getFinalFee() === null;
    }

    public function hasSomeFinalFee(): bool
    {
        return $this->order->getChildObject()->getFinalFee() !== null
            || $this->order->getChildObject()->getApproximatelyFinalFee() > 0;
    }

    public function getFormattedFinalFee(): string
    {
        $value = $this->order->getChildObject()->getFinalFee()
            ?? $this->order->getChildObject()->getApproximatelyFinalFee();

        return $this->formatPrice(
            $this->order->getChildObject()->getCurrency(),
            round((float)$value, 2)
        );
    }

    //########################################

    protected function _toHtml()
    {
        $orderNoteGridId = $this->getChildBlock('order_note_grid')->getId();
        $this->jsTranslator->add('Custom Note', $this->__('Custom Note'))
                           ->add(
                               'ebay_order_fee_sell_api_popup_title',
                               $this->__('Actualize eBay final fee')
                           )
                           ->add(
                               'ebay_order_fee_sell_api_popup_text',
                               $this->__(<<<HTML
    To get actualized data on eBay final fee, you should grant M2E Pro access to your eBay data.
If you consent, click <strong>Confirm</strong>. You will be redirected to M2E Pro eBay Account page. Under the
<strong>Access Details</strong> section, click <strong>Get Token</strong> (the instructions can be found
<a href="%url%" target="_blank">here</a>). After an eBay token is obtained, <strong>Save</strong> the changes to
Account configuration.<br><br>
HTML
                                   ,
                                   $this->supportHelper->getDocumentationArticleUrl('x/xAcVB')
                               )
                           );

        $this->js->add(<<<JS
    require([
        'M2ePro/Order/Note',
    ], function(){
        window.OrderNoteObj = new OrderNote('$orderNoteGridId');
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################
}
