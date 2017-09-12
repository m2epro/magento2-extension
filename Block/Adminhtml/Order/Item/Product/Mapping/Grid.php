<?php

namespace Ess\M2ePro\Block\Adminhtml\Order\Item\Product\Mapping;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

class Grid extends AbstractGrid
{
    //########################################

    protected $cacheConfig;
    protected $productTypeModel;
    protected $productModel;

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Magento\Catalog\Model\Product\Type $productTypeModel,
        \Magento\Catalog\Model\Product $productModel,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->cacheConfig = $cacheConfig;
        $this->productTypeModel = $productTypeModel;
        $this->productModel = $productModel;

        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('orderItemProductMappingGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $collection = $this->productModel->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('type_id')
            ->joinField(
                'qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            )
            ->joinField(
                'is_in_stock',
                'cataloginventory_stock_item',
                'is_in_stock',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header'       => $this->__('Product ID'),
            'align'        => 'right',
            'type'         => 'number',
            'width'        => '60px',
            'index'        => 'entity_id',
            'filter_index' => 'entity_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('title', array(
            'header'       => $this->__('Product Title / Product SKU'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '350px',
            'index'        => 'name',
            'filter_index' => 'name',
            'frame_callback' => array($this, 'callbackColumnTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ));

        $this->addColumn('type_id',array(
            'header'=> $this->__('Type'),
            'width' => '60px',
            'index' => 'type_id',
            'sortable'  => false,
            'type'  => 'options',
            'options' => $this->productTypeModel->getOptionArray()
        ));

        $this->addColumn('stock_availability', array(
            'header'=> $this->__('Stock Availability'),
            'width' => '100px',
            'index' => 'is_in_stock',
            'filter_index' => 'is_in_stock',
            'type'  => 'options',
            'sortable'  => false,
            'options' => array(
                1 => $this->__('In Stock'),
                0 => $this->__('Out of Stock')
            ),
            'frame_callback' => array($this, 'callbackColumnStockAvailability')
        ));

        $this->addColumn('actions', array(
            'header'       => $this->__('Actions'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '125px',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => array($this, 'callbackColumnActions'),
        ));
    }

    //########################################

    public function callbackColumnProductId($productId, $product, $column, $isExport)
    {
        $url = $this->getUrl('catalog/product/edit', array('id' => $productId));
        $withoutImageHtml = '<a href="'.$url.'" target="_blank">'.$productId.'</a>&nbsp;';

        $showProductsThumbnails = (bool)(int)$this->cacheConfig->getGroupValue(
            '/view/','show_products_thumbnails'
        );
        if (!$showProductsThumbnails) {
            return $withoutImageHtml;
        }

        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProduct($product);

        $imageResized = $magentoProduct->getThumbnailImage();
        if (is_null($imageResized)) {
            return $withoutImageHtml;
        }

        $imageResizedUrl = $imageResized->getUrl();

        $imageHtml = $productId.'<div style="margin-top: 5px">'.
            '<img style="max-width: 100px; max-height: 100px;" src="' .$imageResizedUrl. '" /></div>';
        $withImageHtml = str_replace('>'.$productId.'<','>'.$imageHtml.'<',$withoutImageHtml);

        return $withImageHtml;
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $value = '<div style="margin-left: 3px">'.$this->getHelper('Data')->escapeHtml($value);

        $sku = $row->getData('sku');
        if (is_null($sku)) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                ->setProductId($row->getData('entity_id'))->getSku();
        }

        $value .= '<br/><strong>'.$this->__('SKU').':</strong> ';
        $value .= $this->getHelper('Data')->escapeHtml($sku).'</div>';

        return $value;
    }

    public function callbackColumnType($value, $row, $column, $isExport)
    {
        return '<div style="margin-left: 3px">'.$this->getHelper('Data')->escapeHtml($value).'</div>';
    }

    public function callbackColumnStockAvailability($value, $row, $column, $isExport)
    {
        if ((int)$row->getData('is_in_stock') <= 0) {
            return '<span style="color: red;">'.$value.'</span>';
        }

        return $value;
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $productId = (int)$row->getId();
        $productSku = $row->getSku();
        $label = $this->__('Map To This Product');

        $js = <<<JS
OrderEditItemObj.assignProduct('{$productId}', '{$productSku}');
JS;

        $html = <<<HTML
&nbsp;<a href="javascript:void(0);" onclick="{$js}">{$label}</a>
HTML;

        return $html;
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            array(
                array('attribute'=>'sku','like'=>'%'.$value.'%'),
                array('attribute'=>'name', 'like'=>'%'.$value.'%')
            )
        );
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/order/productMappingGrid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}