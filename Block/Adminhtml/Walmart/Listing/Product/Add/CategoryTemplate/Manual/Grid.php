<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplate\Manual;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplate\Manual\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Product\Grid
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing = null;

    protected $magentoProductCollectionFactory;
    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->walmartFactory = $walmartFactory;

        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');

        // Initialization block
        // ---------------------------------------
        $this->setId('newAsinManualGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        // ---------------------------------------

        $this->useAdvancedFilter = false;
    }

    //########################################

    protected function _prepareCollection()
    {
        // Get collection
        // ---------------------------------------
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->setListingProductModeOn();

        $collection
            ->setListing($this->listing)
            ->setStoreId($this->listing->getData('store_id'))
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku');
        // ---------------------------------------

        // ---------------------------------------
        $listingProductsIds = $this->listing->getSetting('additional_data', 'adding_listing_products_ids');

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            [
                'id' => 'id'
            ],
            '{{table}}.listing_id='.(int)$this->listing->getId()
        );
        $wlpTable = $this->activeRecordFactory->getObject('Walmart_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['alp' => $wlpTable],
            'listing_product_id=id',
            [
                'listing_product_id'        => 'listing_product_id',
                'template_category_id'   => 'template_category_id'
            ]
        );

        $collection->getSelect()->where('lp.id IN (?)', $listingProductsIds);
        // ---------------------------------------

        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'filter_index' => 'entity_id',
            'frame_callback' => [$this, 'callbackColumnProductId']
        ]);

        $this->addColumn('name', [
            'header'    => $this->__('Product Title / Product SKU'),
            'align'     => 'left',
            'width'     => '400px',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'name',
            'escape'       => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterProductTitle']
        ]);

        $this->addColumn('category_template', [
            'header'    => $this->__('Category Policy'),
            'align'     => 'left',
            'width'     => '*',
            'sortable'  => false,
            'type'      => 'options',
            'index'     => 'category_template_id',
            'filter_index' => 'category_template_id',
            'options'   => [
                1 => $this->__('Category Policy Selected'),
                0 => $this->__('Category Policy Not Selected')
            ],
            'frame_callback' => [$this, 'callbackColumnCategoryTemplateCallback'],
            'filter_condition_callback' => [$this, 'callbackColumnCategoryTemplateFilterCallback']
        ]);

        $actionsColumn = [
            'header'    => $this->__('Actions'),
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'no_link' => true,
            'align'     => 'center',
            'width'     => '130px',
            'type'      => 'text',
            'field'     => 'id',
            'sortable'  => false,
            'filter'    => false,
            'actions'   => []
        ];

        $actions = [
            [
                'caption' => $this->__('Set Category Policy'),
                'field'   => 'id',
                'onclick_action' => 'ListingGridHandlerObj.setCategoryTemplateRowAction'
            ]
        ];

        $actionsColumn['actions'] = $actions;

        $this->addColumn('actions', $actionsColumn);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('listing_product_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // ---------------------------------------
        $this->getMassactionBlock()->addItem('setCategoryTemplate', [
            'label' => $this->__('Set Category Policy'),
            'url'   => ''
        ]);
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        if (strlen($productTitle) > 60) {
            $productTitle = substr($productTitle, 0, 60) . '...';
        }

        $productTitle = $this->getHelper('Data')->escapeHtml($productTitle);

        $value = '<span>'.$productTitle.'</span>';

        $sku = $row->getData('sku');

        $value .= '<br/><strong>'.$this->__('SKU') .
            ':</strong> '.$this->getHelper('Data')->escapeHtml($sku) . '<br/>';

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $listingProductId = (int)$row->getData('id');
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);
        $walmartListingProduct = $listingProduct->getChildObject();

        if (!$walmartListingProduct->getVariationManager()->isVariationProduct()) {
            return $value;
        }

        if ($walmartListingProduct->getVariationManager()->isRelationParentType()) {
            $productAttributes = (array)$walmartListingProduct->getVariationManager()
                ->getTypeModel()->getProductAttributes();
        } else {
            $productOptions = $walmartListingProduct->getVariationManager()
                ->getTypeModel()->getProductOptions();
            $productAttributes = !empty($productOptions) ? array_keys($productOptions) : [];
        }

        if (!empty($productAttributes)) {
            $value .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin-left: 7px"><br/>';
            $value .= implode(', ', $productAttributes);
            $value .= '</div>';
        }

        return $value;
    }

    public function callbackColumnCategoryTemplateCallback($value, $row, $column, $isExport)
    {
        $categoryTemplateId = $row->getData('template_category_id');

        if (empty($categoryTemplateId)) {
            $label = $this->__('Not Selected');

            return <<<HTML
<span class='icon-warning' style="color: gray; font-style: italic;">{$label}</span>
HTML;
        }

        $templateCategoryEditUrl = $this->getUrl('*/walmart_template_category/edit', [
            'id' => $categoryTemplateId
        ]);

        /** @var \Ess\M2ePro\Model\Walmart\Template\Category $categoryTemplate */
        $categoryTemplate = $this->activeRecordFactory->getObjectLoaded(
            'Walmart_Template_Category',
            $categoryTemplateId
        );

        $title = $this->getHelper('Data')->escapeHtml($categoryTemplate->getData('title'));

        return <<<HTML
<a target="_blank" href="{$templateCategoryEditUrl}">{$title}</a>
HTML;
    }

    //########################################

    protected function callbackFilterProductTitle($collection, $column)
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

    // ---------------------------------------

    protected function callbackColumnCategoryTemplateFilterCallback($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        if ($value) {
            $collection->addFieldToFilter('template_category_id', ['notnull' => null]);
        } else {
            $collection->addFieldToFilter('template_category_id', ['null' => null]);
        }
    }

    //########################################

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    ListingGridHandlerObj.afterInitPage();
JS
            );
        }

        return parent::_toHtml();
    }

    //########################################
}
