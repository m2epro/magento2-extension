<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Category\View\Tabs;

use \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty as OnlineQty;
use \Ess\M2ePro\Block\Adminhtml\Ebay\Category\View\Tabs as CategoryViewTabs;
use \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser as Chooser;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Category\View\Tabs\AbstractGrid
 */
abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Magento\Product\Grid
{
    protected $magentoProductCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);

        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
    }

    //########################################

    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if ($collection) {
            $columnIndex = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
            $collection->getSelect()->order($columnIndex . ' ' . strtoupper($column->getDir()));
        }
        return $this;
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn(
            'product_id',
            [
                'header'    => $this->__('Product ID'),
                'align'     => 'right',
                'width'     => '100px',
                'type'      => 'number',
                'index'     => 'entity_id',
                'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId'

            ]
        );

        $this->addColumn(
            'name',
            [
                'header'    => $this->__('Product Title / Product SKU'),
                'align'     => 'left',
                'width'     => '700px',
                'type'      => 'text',
                'index'     => 'online_title',
                'frame_callback' => [$this, 'callbackColumnTitle'],
                'filter_condition_callback' => [$this, 'callbackFilterTitle']
            ]
        );

        $this->addColumn(
            'is_custom_template',
            [
                'header'       => $this->__('Item Specifics'),
                'width'        => '100px',
                'index'        => 'is_custom_template',
                'filter_index' => 'is_custom_template',
                'type'         => 'options',
                'sortable'     => false,
                'options'      => [
                    1 => $this->__('Custom'),
                    0 => $this->__('Default')
                ]
            ]
        );

        $this->addColumn(
            'ebay_item_id',
            [
                'header'    => $this->__('Item ID'),
                'align'     => 'left',
                'width'     => '100px',
                'type'      => 'text',
                'index'     => 'item_id',
                'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\ItemId'
            ]
        );

        $this->addColumn(
            'available_qty',
            [
                'header'    => $this->__('Available QTY'),
                'align'     => 'right',
                'width'     => '100px',
                'type'      => 'number',
                'index'     => 'available_qty',
                'filter_index'      => 'available_qty',
                'renderer'          => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty',
                'render_online_qty' => OnlineQty::ONLINE_AVAILABLE_QTY,
                'filter_condition_callback' => [$this, 'callbackFilterOnlineQty']
            ]
        );

        $this->addColumn(
            'online_qty_sold',
            [
                'header'   => $this->__('Sold QTY'),
                'align'    => 'right',
                'width'    => '100px',
                'type'     => 'number',
                'index'    => 'online_qty_sold',
                'renderer' => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty'
            ]
        );

        $this->addColumn(
            'price',
            [
                'header'    => $this->__('Price'),
                'align'     =>'right',
                'width'     => '100px',
                'type'      => 'number',
                'index'     => 'online_current_price',
                'filter_index' => 'online_current_price',
                'renderer' => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\CurrentPrice',
                'filter_condition_callback' => [$this, 'callbackFilterPrice']
            ]
        );

        $this->addColumn(
            'end_date',
            [
                'header'   => $this->__('End Date'),
                'align'    => 'right',
                'width'    => '100px',
                'type'     => 'datetime',
                'format'   => \IntlDateFormatter::MEDIUM,
                'index'    => 'end_date',
                'renderer' => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\DateTime'
            ]
        );

        $statusColumn = [
            'header'       => $this->__('Status'),
            'width'        => '100px',
            'index'        => 'status',
            'filter_index' => 'status',
            'type'         => 'options',
            'sortable'     => false,
            'options'      => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED     => $this->__('Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN     => $this->__('Listed (Hidden)'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD       => $this->__('Sold'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED    => $this->__('Stopped'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED   => $this->__('Finished'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED    => $this->__('Pending')
            ],
            'renderer' => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Status',
            'filter_condition_callback' => [$this, 'callbackFilterStatus']
        ];

        $this->addColumn('status', $statusColumn);

        return parent::_prepareColumns();
    }

    //########################################

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        $this->getMassactionBlock()->addItem(
            'editEbayCategory',
            [
                'label' => $this->__('Edit'),
                'url'   => ''
            ]
        );

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $title       = $row->getData('name');
        $onlineTitle = $row->getData('online_title');

        !empty($onlineTitle) && $title = $onlineTitle;

        $title = $this->escapeHtml($title);

        $valueHtml = '<span class="product-title-value">' . $title . '</span>';

        $sku = $row->getData('sku');

        if ($sku === null) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                ->setProductId($row->getData('entity_id'))
                ->getSku();
        }

        $onlineSku = $row->getData('online_sku');
        !empty($onlineSku) && $sku = $onlineSku;

        $valueHtml .=
            '<br/>' . '<strong>' . $this->__('SKU') . ':</strong>&nbsp;' . $this->escapeHtml($sku);

        return $valueHtml;
    }

    // ---------------------------------------

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute'=>'sku','like'=>'%'.$value.'%'],
                ['attribute'=>'online_sku','like'=>'%'.$value.'%'],
                ['attribute'=>'name', 'like'=>'%'.$value.'%'],
                ['attribute'=>'online_title','like'=>'%'.$value.'%']
            ]
        );
    }

    protected function callbackFilterOnlineQty($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $where = '';
        $onlineQty = 'elp.online_qty - elp.online_qty_sold';

        if (isset($cond['from']) || isset($cond['to'])) {
            if (isset($cond['from']) && $cond['from'] != '') {
                $value = $collection->getConnection()->quote($cond['from']);
                $where .= "{$onlineQty} >= {$value}";
            }

            if (isset($cond['to']) && $cond['to'] != '') {
                if (isset($cond['from']) && $cond['from'] != '') {
                    $where .= ' AND ';
                }

                $value = $collection->getConnection()->quote($cond['to']);
                $where .= "{$onlineQty} <= {$value}";
            }
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $collection->addFieldToFilter('online_current_price', $cond);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $index = $column->getIndex();

        if ($value == null) {
            return;
        }

        if (is_array($value) && isset($value['value'])) {
            $collection->addFieldToFilter($index, (int)$value['value']);
        } elseif (!is_array($value) && $value !== null) {
            $collection->addFieldToFilter($index, (int)$value);
        }

        if (is_array($value) && isset($value['is_duplicate'])) {
            $collection->addFieldToFilter('is_duplicate', 1);
        }
    }

    //########################################

    protected function _toHtml()
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
        $template = $this->activeRecordFactory->getObjectLoaded(
            'Ebay_Template_Category',
            $this->getRequest()->getParam('template_id')
        );

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Listing'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay\Category'));

        $categoryMode = $this->getRequest()->getParam('active_tab') == CategoryViewTabs::TAB_ID_PRODUCTS_SECONDARY
            ? Chooser::MODE_EBAY_SECONDARY
            : Chooser::MODE_EBAY_PRIMARY;

        $this->jsUrl->add(
            $this->getUrl('*/ebay_listing/getCategoryChooserHtml', ['category_mode' => $categoryMode]),
            'ebay_listing/getCategoryChooserHtml'
        );

        $this->jsTranslator->addTranslations([
            'Category Settings' => $this->__('Category Settings'),
            'Specifics' => $this->__('Specifics')
        ]);

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'M2ePro/Ebay/Category/Grid',
        'M2ePro/Ebay/Listing/Category'
    ], function(){

        window.EbayCategoryGridObj = new EbayCategoryGrid(
            '{$this->getId()}',
            '{$template->getMarketplaceId()}',
            null,
            '{$this->getRequest()->getParam('template_id')}'
        );
        EbayCategoryGridObj.afterInitPage();
        window.EbayListingCategoryObj = new EbayListingCategory(EbayCategoryGridObj);
    });
JS
        );

        return parent::_toHtml();
    }

    //########################################
}
