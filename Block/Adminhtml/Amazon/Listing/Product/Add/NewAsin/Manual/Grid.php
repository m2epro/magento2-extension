<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\NewAsin\Manual;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Product\Grid
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing = NULL;

    protected $magentoProductCollectionFactory;
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->amazonFactory = $amazonFactory;

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
        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->setListingProductModeOn();

        $collection->setStoreId($this->listing->getData('store_id'))
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku');
        // ---------------------------------------

        // ---------------------------------------
        $listingProductsIds = $this->listing->getSetting('additional_data', 'adding_new_asin_listing_products_ids');

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            array('lp' => $lpTable),
            'product_id=entity_id',
            array(
                'id' => 'id'
            ),
            '{{table}}.listing_id='.(int)$this->listing->getId()
        );
        $alpTable = $this->activeRecordFactory->getObject('Amazon\Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            array('alp' => $alpTable),
            'listing_product_id=id',
            array(
                'listing_product_id'        => 'listing_product_id',
                'template_description_id'   => 'template_description_id'
            )
        );

        $collection->getSelect()->where('lp.id IN (?)', $listingProductsIds);
        $collection->getSelect()->where('alp.search_settings_status != ? OR alp.search_settings_status IS NULL',
            \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS);
        $collection->getSelect()->where('alp.general_id IS NULL');
        // ---------------------------------------

        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', array(
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'filter_index' => 'entity_id',
            'frame_callback' => array($this, 'callbackColumnProductId')
        ));

        $this->addColumn('name', array(
            'header'    => $this->__('Product Title / Product SKU'),
            'align'     => 'left',
            'width'     => '400px',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'name',
            'frame_callback' => array($this, 'callbackColumnProductTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterProductTitle')
        ));

        $this->addColumn('description_template', array(
            'header'    => $this->__('Description Policy'),
            'align'     => 'left',
            'width'     => '*',
            'sortable'  => false,
            'type'      => 'options',
            'index'     => 'description_template_id',
            'filter_index' => 'description_template_id',
            'options'   => array(
                1 => $this->__('Description Policy Selected'),
                0 => $this->__('Description Policy Not Selected')
            ),
            'frame_callback' => array($this, 'callbackColumnDescriptionTemplateCallback'),
            'filter_condition_callback' => array($this, 'callbackColumnDescriptionTemplateFilterCallback')
        ));

        $actionsColumn = array(
            'header'    => $this->__('Actions'),
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'align'     => 'center',
            'width'     => '130px',
            'type'      => 'text',
            'field'     => 'id',
            'sortable'  => false,
            'filter'    => false,
            'actions'   => array()
        );

        $actions = array(
            array(
                'caption' => $this->__('Set Description Policy'),
                'field'   => 'id',
                'onclick_action' => 'ListingGridHandlerObj.setDescriptionTemplateRowAction'
            ),
            array(
                'caption' => $this->__('Reset Description Policy'),
                'field'   => 'id',
                'onclick_action' => 'ListingGridHandlerObj.resetDescriptionTemplateRowAction'
            )
        );

        $actionsColumn['actions'] = $actions;

        $this->addColumn('actions', $actionsColumn);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('listing_product_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // ---------------------------------------
        $this->getMassactionBlock()->addItem('setDescriptionTemplate', array(
            'label' => $this->__('Set Description Policy'),
            'url'   => ''
        ));

        $this->getMassactionBlock()->addItem('resetDescriptionTemplate', array(
            'label' => $this->__('Reset Description Policy'),
            'url'   => ''
        ));
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
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $listingProductId = (int)$row->getData('id');
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);
        $amazonListingProduct = $listingProduct->getChildObject();

        if (!$amazonListingProduct->getVariationManager()->isVariationProduct()) {
            return $value;
        }

        if ($amazonListingProduct->getVariationManager()->isRelationParentType()) {
            $productAttributes = (array)$amazonListingProduct->getVariationManager()
                ->getTypeModel()->getProductAttributes();
        } else {
            $productOptions = $amazonListingProduct->getVariationManager()
                ->getTypeModel()->getProductOptions();
            $productAttributes = !empty($productOptions) ? array_keys($productOptions) : array();
        }

        if (!empty($productAttributes)) {

            $value .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin-left: 7px"><br/>';
            $value .= implode(', ', $productAttributes);
            $value .= '</div>';
        }

        return $value;
    }

    public function callbackColumnDescriptionTemplateCallback($value, $row, $column, $isExport)
    {
        $descriptionTemplateId = $row->getData('template_description_id');

        if (empty($descriptionTemplateId)) {
            $label = $this->__('Not Selected');

            return <<<HTML
<span class='icon-warning' style="color: gray; font-style: italic;">{$label}</span>
HTML;
        }

        $templateDescriptionEditUrl = $this->getUrl('*/amazon_template_description/edit', array(
            'id' => $descriptionTemplateId
        ));

        /** @var \Ess\M2ePro\Model\Amazon\Template\Description $descriptionTemplate */
        $descriptionTemplate = $this->activeRecordFactory->getObjectLoaded(
            'Template\Description', $descriptionTemplateId
        );

        $title = $this->getHelper('Data')->escapeHtml($descriptionTemplate->getData('title'));

        return <<<HTML
<a target="_blank" href="{$templateDescriptionEditUrl}">{$title}</a>
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
            array(
                array('attribute'=>'sku','like'=>'%'.$value.'%'),
                array('attribute'=>'name', 'like'=>'%'.$value.'%')
            )
        );
    }

    // ---------------------------------------

    protected function callbackColumnDescriptionTemplateFilterCallback($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        if ($value) {
            $collection->addFieldToFilter('template_description_id', array('notnull' => null));
        } else {
            $collection->addFieldToFilter('template_description_id', array('null' => null));
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