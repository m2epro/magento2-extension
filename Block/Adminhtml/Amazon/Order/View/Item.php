<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order\View;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Order\View\Item
 */
class Item extends AbstractGrid
{
    /** @var \Ess\M2ePro\Model\Order $order */
    protected $order = null;

    protected $productModel;
    protected $resourceConnection;
    protected $amazonFactory;

    //########################################

    /**
     * {@inheritDoc}
     */
    public function __construct(
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->productModel = $productModel;
        $this->resourceConnection = $resourceConnection;
        $this->amazonFactory = $amazonFactory;

        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonOrderViewItem');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
        $this->setUseAjax(true);
        $this->_defaultLimit = 200;
        // ---------------------------------------

        $this->order = $this->getHelper('Data_GlobalData')->getValue('order');
    }

    //########################################

    /**
     * {@inheritDoc}
     */
    protected function _prepareCollection()
    {
        $collection = $this->amazonFactory->getObject('Order_Item')->getCollection()
            ->addFieldToFilter('order_id', $this->order->getId());

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * {@inheritDoc}
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('products', [
            'header'    => $this->__('Product'),
            'align'     => 'left',
            'width'     => '*',
            'index'     => 'product_id',
            'frame_callback' => [$this, 'callbackColumnProduct']
        ]);

        $this->addColumn('stock_availability', [
            'header'=> $this->__('Stock Availability'),
            'width' => '100px',
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnIsInStock']
        ]);

        $this->addColumn('original_price', [
            'header'    => $this->__('Original Price'),
            'align'     => 'left',
            'width'     => '80px',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnOriginalPrice']
        ]);

        $this->addColumn('qty_purchased', [
            'header'    => $this->__('QTY'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'qty_purchased',
            'frame_callback' => [$this, 'callbackColumnQty']
        ]);

        $this->addColumn('price', [
            'header'    => $this->__('Price'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'price',
            'frame_callback' => [$this, 'callbackColumnPrice']
        ]);

        $this->addColumn('discount_amount', [
            'header'    => $this->__('Promotions'),
            'align'     => 'left',
            'width'     => '80px',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnDiscountAmount']
        ]);

        if ($this->activeRecordFactory->getObject('Amazon_Order')->getResource()->hasGifts($this->order->getId())) {
            $this->addColumn('gift_price', [
                'header'    => $this->__('Gift Wrap Price'),
                'align'     => 'left',
                'width'     => '80px',
                'index'     => 'gift_price',
                'frame_callback' => [$this, 'callbackColumnGiftPrice']
            ]);

            $this->addColumn('gift_options', [
                'header'    => $this->__('Gift Options'),
                'align'     => 'left',
                'width'     => '250px',
                'filter'    => false,
                'sortable'  => false,
                'frame_callback' => [$this, 'callbackColumnGiftOptions']
            ]);
        }

        $this->addColumn('tax_percent', [
            'header'         => $this->__('Tax Percent'),
            'align'          => 'left',
            'width'          => '80px',
            'filter'         => false,
            'sortable'       => false,
            'frame_callback' => [$this, 'callbackColumnTaxPercent']
        ]);

        $this->addColumn('row_total', [
            'header'    => $this->__('Row Total'),
            'align'     => 'left',
            'width'     => '80px',
            'frame_callback' => [$this, 'callbackColumnRowTotal']
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    /**
     * @param string                                             $value
     * @param \Ess\M2ePro\Model\Order\Item                       $row
     * @param \Magento\Backend\Block\Widget\Grid\Column\Extended $column
     * @param bool                                               $isExport
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function callbackColumnProduct($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Helper\Data $dataHelper */
        $dataHelper = $this->getHelper('Data');

        /** @var \Ess\M2ePro\Helper\Module\Translation $translationHelper */
        $translationHelper = $this->getHelper('Module_Translation');

        $amazonOrderItem = $row->getChildObject();
        $skuHtml = '';
        if ($amazonOrderItem->getSku()) {
            $skuHtml = <<<HTML
<b>{$translationHelper->__('SKU')}:</b> {$dataHelper->escapeHtml($amazonOrderItem->getSku())}&nbsp;&nbsp;&nbsp;
HTML;
        }

        $generalIdLabel = $translationHelper->__($amazonOrderItem->getIsIsbnGeneralId() ? 'ISBN' : 'ASIN');
        $generalIdHtml = <<<HTML
<b>{$generalIdLabel}:</b> {$dataHelper->escapeHtml($amazonOrderItem->getGeneralId())}&nbsp;&nbsp;&nbsp;
HTML;

        $afnWarehouseHtml = '';
        if ($row->getOrder()->getChildObject()->isFulfilledByAmazon()) {
            $fulfillmentCenterId = $amazonOrderItem->getFulfillmentCenterId() ?: 'Pending';
            $afnWarehouseHtml = <<<HTML
<b>{$translationHelper->__('AFN Warehouse')}:</b> {$dataHelper->escapeHtml($fulfillmentCenterId)}<br/>
HTML;
        }

        $amazonLink = '';
        if (!$amazonOrderItem->getIsIsbnGeneralId() || !$dataHelper->isISBN($amazonOrderItem->getGeneralId())) {
            $itemUrl = $this->getHelper('Component_Amazon')->getItemUrl(
                $amazonOrderItem->getGeneralId(),
                $this->order->getMarketplaceId()
            );
            $amazonLink = <<<HTML
<a href="{$itemUrl}" target="_blank">{$translationHelper->__('View on Amazon')}</a>
HTML;
        }

        $productLink = '';
        if ($productId = $row->getData('product_id')) {
            $productUrl = $this->getUrl('catalog/product/edit', [
                'id'    => $productId,
                'store' => $row->getOrder()->getStoreId()
            ]);
            $productLink = <<<HTML
<a href="{$productUrl}" class="external-link" target="_blank">{$translationHelper->__('View')}</a>
HTML;
        }

        $amazonLink && $productLink && $amazonLink .= '&nbsp;|&nbsp;';
        $jsTemplate = <<<HTML
<a class="gray" href="javascript:void(0);" onclick="
{OrderEditItemObj.%s('{$this->getId()}', {$row->getId()});}
">%s</a>
HTML;

        $editLink = '';
        if (!$row->getProductId()) {
            $editLink = sprintf($jsTemplate, 'edit', $translationHelper->__('Link to Magento Product'));
        }

        $isPretendedToBeSimple = false;
        if ($amazonOrderItem->getParentObject()->getMagentoProduct() !== null &&
            $amazonOrderItem->getParentObject()->getMagentoProduct()->isGroupedType() &&
            $amazonOrderItem->getChannelItem() !== null) {
            $isPretendedToBeSimple = $amazonOrderItem->getChannelItem()->isGroupedProductModeSet();
        }

        if ($row->getProductId() && $row->getMagentoProduct()->isProductWithVariations() && !$isPretendedToBeSimple) {
            $editLink = sprintf($jsTemplate, 'edit', $translationHelper->__('Set Options')) . '&nbsp;|&nbsp;';
        }

        $discardLink = '';
        if ($row->getProductId()) {
            $discardLink = sprintf($jsTemplate, 'unassignProduct', $translationHelper->__('Unlink'));
        }

        return <<<HTML
<b>{$dataHelper->escapeHtml($amazonOrderItem->getTitle())}</b><br/>
<div style="padding-left: 10px;">
    {$skuHtml}{$generalIdHtml}
    {$afnWarehouseHtml}
</div>
<div style="float: left;">{$amazonLink}{$productLink}</div>
<div style="float: right;">{$editLink}{$discardLink}</div>
HTML;
    }

    /**
     * @param string                                             $value
     * @param \Ess\M2ePro\Model\Order\Item                       $row
     * @param \Magento\Backend\Block\Widget\Grid\Column\Extended $column
     * @param bool                                               $isExport
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function callbackColumnIsInStock($value, $row, $column, $isExport)
    {
        if ($row->getMagentoProduct() === null) {
            return $this->__('N/A');
        }

        if (!$row->getMagentoProduct()->isStockAvailability()) {
            return '<span style="color: red;">' . $this->__('Out Of Stock') . '</span>';
        }

        return $this->__('In Stock');
    }

    /**
     * @param string                                             $value
     * @param \Ess\M2ePro\Model\Order\Item                       $row
     * @param \Magento\Backend\Block\Widget\Grid\Column\Extended $column
     * @param bool                                               $isExport
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function callbackColumnOriginalPrice($value, $row, $column, $isExport)
    {
        $productId = $row->getData('product_id');
        $formattedPrice = $this->__('N/A');

        if ($productId && $product = $this->productModel->load($productId)) {
            $formattedPrice = $product->getFormatedPrice();
        }

        return $formattedPrice;
    }

    /**
     * @param string                                             $value
     * @param \Ess\M2ePro\Model\Order\Item                       $row
     * @param \Magento\Backend\Block\Widget\Grid\Column\Extended $column
     * @param bool                                               $isExport
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function callbackColumnQty($value, $row, $column, $isExport)
    {
        return $row->getChildObject()->getData('qty_purchased');
    }

    /**
     * @param string                                             $value
     * @param \Ess\M2ePro\Model\Order\Item                       $row
     * @param \Magento\Backend\Block\Widget\Grid\Column\Extended $column
     * @param bool                                               $isExport
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        $currency = $row->getChildObject()->getData('currency');
        if (empty($currency)) {
            $currency = $this->order->getMarketplace()->getChildObject()->getDefaultCurrency();
        }

        return $this->modelFactory->getObject('Currency')->formatPrice(
            $currency,
            $row->getChildObject()->getData('price')
        );
    }

    /**
     * @param string                                             $value
     * @param \Ess\M2ePro\Model\Order\Item                       $row
     * @param \Magento\Backend\Block\Widget\Grid\Column\Extended $column
     * @param bool                                               $isExport
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function callbackColumnGiftPrice($value, $row, $column, $isExport)
    {
        $currency = $row->getChildObject()->getData('currency');
        if (empty($currency)) {
            $currency = $this->order->getMarketplace()->getChildObject()->getDefaultCurrency();
        }

        return $this->modelFactory->getObject('Currency')->formatPrice(
            $currency,
            $row->getChildObject()->getData('gift_price')
        );
    }

    /**
     * @param string                                             $value
     * @param \Ess\M2ePro\Model\Order\Item                       $row
     * @param \Magento\Backend\Block\Widget\Grid\Column\Extended $column
     * @param bool                                               $isExport
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function callbackColumnDiscountAmount($value, $row, $column, $isExport)
    {
        $currency = $row->getChildObject()->getData('currency');
        if (empty($currency)) {
            $currency = $this->order->getMarketplace()->getChildObject()->getDefaultCurrency();
        }

        $discountDetails = $row->getChildObject()->getData('discount_details');
        if (empty($discountDetails)) {
            return $this->modelFactory->getObject('Currency')->formatPrice($currency, 0);
        }

        $discountDetails = $this->getHelper('Data')->jsonDecode($row->getChildObject()->getData('discount_details'));
        if (empty($discountDetails['promotion']['value'])) {
            return $this->modelFactory->getObject('Currency')->formatPrice($currency, 0);
        }

        return $this->modelFactory->getObject('Currency')->formatPrice(
            $currency,
            $discountDetails['promotion']['value']
        );
    }

    /**
     * @param string                                             $value
     * @param \Ess\M2ePro\Model\Order\Item                       $row
     * @param \Magento\Backend\Block\Widget\Grid\Column\Extended $column
     * @param bool                                               $isExport
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function callbackColumnGiftOptions($value, $row, $column, $isExport)
    {
        if ($row->getChildObject()->getData('gift_type') == '' &&
            $row->getChildObject()->getData('gift_message') == ''
        ) {
            return $this->__('N/A');
        }

        $giftType = $this->getHelper('Data')->escapeHtml($row->getChildObject()->getData('gift_type'));
        $giftTypeLabel = $this->__('Gift Wrap Type');

        $giftMessage = $this->getHelper('Data')->escapeHtml($row->getChildObject()->getData('gift_message'));
        $giftMessageLabel = $this->__('Gift Message');

        $resultHtml = '';
        if (!empty($giftType)) {
            $resultHtml .= "<strong>{$giftTypeLabel}: </strong>{$giftType}<br/>";
        }

        $resultHtml .= "<strong>{$giftMessageLabel}: </strong>{$giftMessage}";

        return $resultHtml;
    }

    /**
     * @param string                                             $value
     * @param \Ess\M2ePro\Model\Order\Item                       $row
     * @param \Magento\Backend\Block\Widget\Grid\Column\Extended $column
     * @param bool                                               $isExport
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function callbackColumnTaxPercent($value, $row, $column, $isExport)
    {
        $rate = $this->order->getChildObject()->getProductPriceTaxRate();
        if (empty($rate)) {
            return '0%';
        }

        return sprintf('%s%%', $rate);
    }

    /**
     * @param string                                             $value
     * @param \Ess\M2ePro\Model\Order\Item                       $row
     * @param \Magento\Backend\Block\Widget\Grid\Column\Extended $column
     * @param bool                                               $isExport
     *
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function callbackColumnRowTotal($value, $row, $column, $isExport)
    {
        $aOrderItem = $row->getChildObject();

        $currency = $row->getData('currency');
        if (empty($currency)) {
            $currency = $this->order->getMarketplace()->getChildObject()->getDefaultCurrency();
        }

        $price = $aOrderItem->getPrice() + $aOrderItem->getGiftPrice() + $aOrderItem->getTaxAmount();
        $price = $price - $aOrderItem->getDiscountAmount();

        return $this->modelFactory->getObject('Currency')->formatPrice(
            $currency,
            $price * $aOrderItem->getQtyPurchased()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getRowUrl($row)
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/orderItemGrid', ['_current' => true]);
    }

    //########################################
}
