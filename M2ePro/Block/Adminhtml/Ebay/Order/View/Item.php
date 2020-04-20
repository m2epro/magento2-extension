<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Order\View;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Order\View\Item
 */
class Item extends AbstractGrid
{
    /** @var $order \Ess\M2ePro\Model\Order */
    private $order;

    protected $productModel;
    protected $resourceConnection;
    protected $ebayFactory;
    protected $taxCalculator;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Tax\Model\Calculation $taxCalculator,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->productModel = $productModel;
        $this->resourceConnection = $resourceConnection;
        $this->ebayFactory = $ebayFactory;
        $this->taxCalculator = $taxCalculator;

        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayOrderViewItem');
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

    protected function _prepareCollection()
    {
        $collection = $this->ebayFactory->getObject('Order\Item')->getCollection()
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

        $this->addColumn('price', [
            'header'    => $this->__('Price'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'price',
            'frame_callback' => [$this, 'callbackColumnPrice']
        ]);

        $this->addColumn('qty_sold', [
            'header'    => $this->__('QTY'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'qty_purchased',
            'frame_callback' => [$this, 'callbackColumnQty']
        ]);

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
            'filter'    => false,
            'sortable'  => false,
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
        $html = '<b>'.$this->getHelper('Data')->escapeHtml($row->getChildObject()->getTitle()).'</b><br/>';

        $variation = $row->getChildObject()->getVariationOptions();
        if (!empty($variation)) {
            foreach ($variation as $optionName => $optionValue) {
                $optionNameHtml = $this->getHelper('Data')->escapeHtml($optionName);
                $optionValueHtml = $this->getHelper('Data')->escapeHtml($optionValue);

                $html .= <<<HTML
<span style="font-weight: bold; font-style: italic; padding-left: 10px;">
{$optionNameHtml}:&nbsp;
</span>
{$optionValueHtml}<br/>
HTML;
            }
        }

        $itemUrl = $this->getHelper('Component\Ebay')->getItemUrl(
            $row->getChildObject()->getData('item_id'),
            $this->order->getAccount()->getChildObject()->getMode(),
            $this->order->getMarketplaceId()
        );

        $itemLink = '<a href="'.$itemUrl.'" class="external-link" target="_blank">'.$this->__('View on eBay').'</a>';

        $productLink = '';

        if ($productId = $row->getData('product_id')) {
            $productUrl = $this->getUrl('catalog/product/edit', [
                'id'    => $productId,
                'store' => $row->getOrder()->getStoreId()
            ]);
            $productLink .= ' | <a href="'.$productUrl.'" target="_blank">'.$this->__('View').'</a>';
        }

        $html .= <<<HTML
<div style="float: left;">
{$itemLink}{$productLink}
</div>
HTML;

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

        $html .= <<<HTML
<div style="float: right;">
{$editLink}{$discardLink}
</div>
<div style="clear: both;"></div>
HTML;

        return $html;
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
        $formattedPrice = $this->__('N/A');

        $product = $row->getProduct();

        if ($product) {
            /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
            $magentoProduct = $this->modelFactory->getObject('Magento\Product');
            $magentoProduct->setProduct($product);

            if ($magentoProduct->isGroupedType()) {
                $associatedProducts = $row->getAssociatedProducts();
                $price = $this->productModel
                    ->load(array_shift($associatedProducts))
                    ->getPrice();

                $formattedPrice = $this->order->getStore()->getCurrentCurrency()->format($price);
            } else {
                $formattedPrice = $this->order->getStore()
                    ->getCurrentCurrency()
                    ->format($row->getProduct()->getPrice());
            }
        }

        return $formattedPrice;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        return $this->modelFactory->getObject('Currency')->formatPrice(
            $this->order->getChildObject()->getCurrency(),
            $row->getChildObject()->getData('price')
        );
    }

    public function callbackColumnQty($value, $row, $column, $isExport)
    {
        return $row->getChildObject()->getData('qty_purchased');
    }

    public function callbackColumnTaxPercent($value, $row, $column, $isExport)
    {
        $taxDetails = $row->getChildObject()->getData('tax_details');
        if (empty($taxDetails)) {
            return '0%';
        }

        $taxDetails = $this->getHelper('Data')->jsonDecode($taxDetails);
        if (empty($taxDetails)) {
            return '0%';
        }

        return sprintf('%s%%', $taxDetails['rate']);
    }

    public function callbackColumnRowTotal($value, $row, $column, $isExport)
    {
        $total = $row->getChildObject()->getData('qty_purchased') * $row->getChildObject()->getData('price');

        $taxDetails = $row->getChildObject()->getData('tax_details');
        if (!empty($taxDetails)) {
            $taxDetails = $this->getHelper('Data')->jsonDecode($taxDetails);

            if (!empty($taxDetails['amount'])) {
                $total += $taxDetails['amount'];
            }
        }

        return $this->modelFactory->getObject('Currency')->formatPrice(
            $this->order->getChildObject()->getCurrency(),
            $total
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
