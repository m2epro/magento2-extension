<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order\Item\Product\Mapping;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Order\Item\Product\Mapping\Grid
 */
class Grid extends AbstractGrid
{
    //########################################

    protected $cacheConfig;
    protected $productTypeModel;
    protected $magentoProductCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Magento\Catalog\Model\Product\Type $productTypeModel,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->cacheConfig = $cacheConfig;
        $this->productTypeModel = $productTypeModel;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;

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
        /** @var $orderItem \Ess\M2ePro\Model\Order\Item */
        $itemId = $this->getRequest()->getParam('item_id');
        $orderItem = $this->activeRecordFactory->getObjectLoaded('Order\Item', $itemId);

        $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        if ($orderItem->getId() !== null) {
            $storeId = $orderItem->getStoreId();
        }

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->setStoreId($storeId);

        $collection
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('type_id')
            ->joinStockItem();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'       => $this->__('Product ID'),
            'align'        => 'right',
            'type'         => 'number',
            'width'        => '60px',
            'index'        => 'entity_id',
            'filter_index' => 'entity_id',
            'frame_callback' => [$this, 'callbackColumnProductId']
        ]);

        $this->addColumn('title', [
            'header'       => $this->__('Product Title / Product SKU'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '350px',
            'index'        => 'name',
            'filter_index' => 'name',
            'escape'       => false,
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('type_id', [
            'header'=> $this->__('Type'),
            'width' => '60px',
            'index' => 'type_id',
            'sortable'  => false,
            'type'  => 'options',
            'options' => $this->productTypeModel->getOptionArray()
        ]);

        $this->addColumn('stock_availability', [
            'header'=> $this->__('Stock Availability'),
            'width' => '100px',
            'index' => 'is_in_stock',
            'filter_index' => 'is_in_stock',
            'type'  => 'options',
            'sortable'  => false,
            'options' => [
                1 => $this->__('In Stock'),
                0 => $this->__('Out of Stock')
            ],
            'frame_callback' => [$this, 'callbackColumnIsInStock']
        ]);

        $this->addColumn('actions', [
            'header'       => $this->__('Actions'),
            'align'        => 'left',
            'type'         => 'text',
            'width'        => '125px',
            'filter'       => false,
            'sortable'     => false,
            'frame_callback' => [$this, 'callbackColumnActions'],
        ]);
    }

    //########################################

    public function callbackColumnProductId($productId, $product, $column, $isExport)
    {
        $url = $this->getUrl('catalog/product/edit', ['id' => $productId]);
        $withoutImageHtml = '<a href="'.$url.'" target="_blank">'.$productId.'</a>&nbsp;';

        $showProductsThumbnails = (bool)(int)$this->cacheConfig->getGroupValue(
            '/view/',
            'show_products_thumbnails'
        );
        if (!$showProductsThumbnails) {
            return $withoutImageHtml;
        }

        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProduct($product);

        $imageResized = $magentoProduct->getThumbnailImage();
        if ($imageResized === null) {
            return $withoutImageHtml;
        }

        $imageResizedUrl = $imageResized->getUrl();

        $imageHtml = $productId.'<div style="margin-top: 5px">'.
            '<img style="max-width: 100px; max-height: 100px;" src="' .$imageResizedUrl. '" /></div>';
        $withImageHtml = str_replace('>'.$productId.'<', '>'.$imageHtml.'<', $withoutImageHtml);

        return $withImageHtml;
    }

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $value = '<div style="margin-left: 3px">'.$this->getHelper('Data')->escapeHtml($value);

        $sku = $row->getData('sku');
        if ($sku === null) {
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

    public function callbackColumnIsInStock($value, $row, $column, $isExport)
    {
        if ($row->getData('is_in_stock') === null) {
            return $this->__('N/A');
        }

        if ((int)$row->getData('is_in_stock') <= 0) {
            return '<span style="color: red;">'.$this->__('Out of Stock').'</span>';
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
            [
                ['attribute'=>'sku','like'=>'%'.$value.'%'],
                ['attribute'=>'name', 'like'=>'%'.$value.'%']
            ]
        );
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/order/productMappingGrid', ['_current'=>true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
}
