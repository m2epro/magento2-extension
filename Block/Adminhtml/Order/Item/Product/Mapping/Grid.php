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

    protected $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;

    protected $productTypeModel;
    protected $magentoProductCollectionFactory;

    public function __construct(
        \Magento\Catalog\Model\Product\Type $productTypeModel,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
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

        if ($orderItem->getId() !== null) {
            $this->storeId = $orderItem->getStoreId();
        }

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->setStoreId($this->storeId);

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
            'store_id'     => $this->storeId,
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId'
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
        $label = $this->__('Link To This Product');

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
