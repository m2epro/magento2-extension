<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Order\View;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Order\View\Item
 */
class Item extends AbstractGrid
{
    /** @var $order \Ess\M2ePro\Model\Order */
    protected $order = null;

    protected $itemSkuToWalmartItemCache;

    protected $productModel;
    protected $resourceConnection;
    protected $walmartFactory;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->productModel = $productModel;
        $this->resourceConnection = $resourceConnection;
        $this->walmartFactory = $walmartFactory;

        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartOrderViewItem');
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
        $collection = $this->walmartFactory->getObject('Order\Item')->getCollection()
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

        $this->addColumn('qty', [
            'header'    => $this->__('QTY'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'qty',
            'frame_callback' => [$this, 'callbackColumnQty']
        ]);

        $this->addColumn('price', [
            'header'    => $this->__('Price'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'price',
            'frame_callback' => [$this, 'callbackColumnPrice']
        ]);

        $this->addColumn('tax_percent', [
            'header'    => $this->__('Tax Percent'),
            'align'     => 'left',
            'width'     => '80px',
            'filter'    => false,
            'sortable'  => false,
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

    protected function _afterLoadCollection()
    {
        $cache = [];
        $skus = [];

        foreach ($this->getCollection()->getItems() as $item) {
            $skus[] = $item->getChildObject()->getSku();
        }

        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $collection->joinListingTable();

        $collection->addFieldToFilter('sku', ['in' => $skus]);
        $collection->addFieldToFilter('l.account_id', $this->order->getAccountId());
        $collection->addFieldToFilter('l.marketplace_id', $this->order->getMarketplaceId());

        foreach ($collection->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Listing\Product $item */
            $sku = (string)$item->getChildObject()->getSku();
            $itemId = (string)$item->getChildObject()->getItemId();

            if ($itemId) {
                $cache[$sku] = $itemId;
            }
        }
        // ---------------------------------------

        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection $collection */
        $collection = $this->walmartFactory->getObject('Listing\Other')->getCollection();

        $collection->addFieldToFilter('sku', ['in' => $skus]);
        $collection->addFieldToFilter('account_id', $this->order->getAccountId());
        $collection->addFieldToFilter('marketplace_id', $this->order->getMarketplaceId());

        foreach ($collection->getItems() as $item) {
            /**@var \Ess\M2ePro\Model\Listing\Other $item */
            $sku = (string)$item->getChildObject()->getSku();
            $itemId = (string)$item->getChildObject()->getItemId();

            if ($itemId && empty($cache[$sku])) {
                $cache[$sku] = $itemId;
            }
        }
        // ---------------------------------------

        $this->itemSkuToWalmartItemCache = $cache;

        return parent::_afterLoadCollection();
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

        $walmartLink = '';
        if (!empty($this->itemSkuToWalmartItemCache[$row->getSku()])) {
            $itemLinkText = $this->__('View on Walmart');
            $itemUrl = $this->getHelper('Component\Walmart')->getItemUrl(
                $this->itemSkuToWalmartItemCache[$row->getSku()],
                $this->order->getData('marketplace_id')
            );

            $walmartLink = <<<HTML
<a href="{$itemUrl}" class="external-link" target="_blank">{$itemLinkText}</a>
HTML;
        }

        $productLink = '';
        if ($productId = $row->getData('product_id')) {
            $productUrl = $this->getUrl('catalog/product/edit', [
                'id'    => $productId,
                'store' => $row->getOrder()->getStoreId()
            ]);
            !empty($walmartLink) && $walmartLink .= ' | ';
            $productLink = '<a href="'.$productUrl.'" target="_blank">'.$this->__('View').'</a>';
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
</div>
<div style="float: left;">{$walmartLink}{$productLink}</div>
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
        return $row->getChildObject()->getData('qty');
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

    public function callbackColumnTaxPercent($value, $row, $column, $isExport)
    {
        $rate = $this->order->getChildObject()->getProductPriceTaxRate();
        if (empty($rate)) {
            return '0%';
        }

        return sprintf('%s%%', $rate);
    }

    public function callbackColumnRowTotal($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Model\Order\Item $row */
        /** @var \Ess\M2ePro\Model\Walmart\Order\Item $aOrderItem */
        $aOrderItem = $row->getChildObject();

        $currency = $row->getData('currency');
        if (empty($currency)) {
            $currency = $this->order->getMarketplace()->getChildObject()->getDefaultCurrency();
        }

        $price = $aOrderItem->getPrice();

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
