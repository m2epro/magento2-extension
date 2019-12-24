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
    /** @var $order \Ess\M2ePro\Model\Order */
    protected $order = null;

    protected $productModel;
    protected $resourceConnection;
    protected $amazonFactory;

    //########################################

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

        $this->order = $this->getHelper('Data\GlobalData')->getValue('order');
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->amazonFactory->getObject('Order\Item')->getCollection()
            ->addFieldToFilter('order_id', $this->order->getId());

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

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

        if ($this->activeRecordFactory->getObject('Amazon\Order')->getResource()->hasGifts($this->order->getId())) {
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
     * @param $value
     * @param $row \Ess\M2ePro\Model\Order\Item
     * @param $column
     * @param $isExport
     *
     * @return string
     */
    public function callbackColumnProduct($value, $row, $column, $isExport)
    {
        $skuHtml = '';
        if ($row->getChildObject()->getSku()) {
            $skuLabel = $this->__('SKU');
            $sku = $this->getHelper('Data')->escapeHtml($row->getChildObject()->getSku());

            $skuHtml = <<<HTML
<b>{$skuLabel}:</b> {$sku}<br/>
HTML;
        }

        $generalIdLabel = $this->__($row->getChildObject()->getIsIsbnGeneralId() ? 'ISBN' : 'ASIN');
        $generalId = $this->getHelper('Data')->escapeHtml($row->getChildObject()->getGeneralId());

        $generalIdHtml = <<<HTML
<b>{$generalIdLabel}:</b> {$generalId}<br/>
HTML;

        if ($row->getChildObject()->getIsIsbnGeneralId() &&
            !$this->getHelper('Data')->isISBN($row->getChildObject()->getGeneralId())
        ) {
            $amazonLink = '';
        } else {
            $itemLinkText = $this->__('View on Amazon');
            $itemUrl = $this->getHelper('Component\Amazon')->getItemUrl(
                $row->getChildObject()->getData('general_id'),
                $this->order->getData('marketplace_id')
            );

            $amazonLink = <<<HTML
<a href="{$itemUrl}" class="external-link" target="_blank">{$itemLinkText}</a>
HTML;
        }

        $productLink = '';
        if ($productId = $row->getData('product_id')) {
            $productUrl = $this->getUrl('catalog/product/edit', [
                'id'    => $productId,
                'store' => $row->getOrder()->getStoreId()
            ]);
            $productLink = ' | <a href="'.$productUrl.'" target="_blank">'.$this->__('View').'</a>';
        }

        $orderItemId = (int)$row->getId();
        $gridId = $this->getId();

        $editLink = '';
        if (!$row->getProductId() || $row->getMagentoProduct()->isProductWithVariations()) {
            if (!$row->getProductId()) {
                $action = $this->__('Map to Magento Product');
            } else {
                $action = $this->__('Set Options');
            }

            $class = 'class="gray"';

            $js = "{OrderEditItemObj.edit('{$gridId}', {$orderItemId});}";
            $editLink = '<a href="javascript:void(0);" onclick="'.$js.'" '.$class.'>'.$action.'</a>';
        }

        $discardLink = '';
        if ($row->getProductId()) {
            $action = $this->__('Unmap');

            $js = "{OrderEditItemObj.unassignProduct('{$gridId}', {$orderItemId});}";
            $discardLink = '<a href="javascript:void(0);" onclick="'.$js.'" class="gray">'.$action.'</a>';

            if ($editLink) {
                $discardLink = '&nbsp;|&nbsp;' . $discardLink;
            }
        }

        $itemTitle = $this->getHelper('Data')->escapeHtml($row->getChildObject()->getTitle());

        return <<<HTML
<b>{$itemTitle}</b><br/>
<div style="padding-left: 10px;">
    {$skuHtml}
    {$generalIdHtml}
</div>
<div style="float: left;">{$amazonLink}{$productLink}</div>
<div style="float: right;">{$editLink}{$discardLink}</div>
HTML;
    }

    public function callbackColumnIsInStock($value, $row, $column, $isExport)
    {
        /**@var \Ess\M2ePro\Model\Order\Item $row */

        if ($row->getMagentoProduct() === null) {
            return $this->__('N/A');
        }

        if (!$row->getMagentoProduct()->isStockAvailability()) {
            return '<span style="color: red;">'.$this->__('Out Of Stock').'</span>';
        }

        return $this->__('In Stock');
    }

    public function callbackColumnOriginalPrice($value, $row, $column, $isExport)
    {
        $productId = $row->getData('product_id');
        $formattedPrice = $this->__('N/A');

        if ($productId && $product = $this->productModel->load($productId)) {
            $formattedPrice = $product->getFormatedPrice();
        }

        return $formattedPrice;
    }

    public function callbackColumnQty($value, $row, $column, $isExport)
    {
        return $row->getChildObject()->getData('qty_purchased');
    }

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

    public function callbackColumnRowTotal($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Model\Order\Item $row */
        /** @var \Ess\M2ePro\Model\Amazon\Order\Item $aOrderItem */
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

    public function getRowUrl($row)
    {
        return '';
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/orderItemGrid', ['_current' => true]);
    }

    //########################################
}
