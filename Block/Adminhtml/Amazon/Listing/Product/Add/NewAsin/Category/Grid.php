<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\NewAsin\Category;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Category\Grid
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing = NULL;

    protected $amazonFactory;
    protected $resourceConnection;
    protected $magentoProductCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Magento\Category\CollectionFactory $categoryCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->amazonFactory = $amazonFactory;
        $this->resourceConnection = $resourceConnection;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;

        parent::__construct($categoryCollectionFactory, $context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_add');

        // Initialization block
        // ---------------------------------------
        $this->setId('newAsinCategoryGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        // ---------------------------------------

        $this->prepareDataByCategories();
    }

    //########################################

    protected function _prepareCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Category\Collection $collection */
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');

        $collection->addFieldToFilter(array(
            array('attribute' => 'entity_id', 'in' => array_keys($this->getData('categories_data')))
        ));

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('magento_category', array(
            'header'    => $this->__('Magento Category'),
            'align'     => 'left',
            'width'     => '500px',
            'type'      => 'text',
            'index'     => 'name',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnMagentoCategory')
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
            'sortable'  => false,
            'filter'    => false,
            'actions'   => array()
        );

        $actions = array(
            array(
                'caption' => $this->__('Set Description Policy'),
                'field'   => 'entity_id',
                'onclick_action' => 'ListingGridHandlerObj.setDescriptionTemplateByCategoryRowAction'
            ),
            array(
                'caption' => $this->__('Reset Description Policy'),
                'field'   => 'entity_id',
                'onclick_action' => 'ListingGridHandlerObj.resetDescriptionTemplateByCategoryRowAction'
            )
        );

        $actionsColumn['actions'] = $actions;

        $this->addColumn('actions', $actionsColumn);

        return parent::_prepareColumns();
    }

    //########################################

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // ---------------------------------------
        $this->getMassactionBlock()->addItem('setDescriptionTemplateByCategory', array(
            'label' => $this->__('Set Description Policy'),
            'url'   => ''
        ));

        $this->getMassactionBlock()->addItem('resetDescriptionTemplateByCategory', array(
            'label' => $this->__('Reset Description Policy'),
            'url'   => ''
        ));
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnDescriptionTemplateCallback($value, $row, $column, $isExport)
    {
        $categoriesData = $this->getData('categories_data');
        $productsIds = implode(',', $categoriesData[$row->getData('entity_id')]);

        $descriptionTemplatesData = $this->getData('description_templates_data');
        $descriptionTemplatesIds = array();
        foreach ($categoriesData[$row->getData('entity_id')] as $productId) {
            if (empty($descriptionTemplatesIds[$descriptionTemplatesData[$productId]])) {
                $descriptionTemplatesIds[$descriptionTemplatesData[$productId]] = 0;
            }
            $descriptionTemplatesIds[$descriptionTemplatesData[$productId]]++;
        }

        arsort($descriptionTemplatesIds);

        reset($descriptionTemplatesIds);
        $descriptionTemplateId = key($descriptionTemplatesIds);

        if (empty($descriptionTemplateId)) {
            $label = $this->__('Not Selected');

            return <<<HTML
<span class="icon-warning" style="color: gray; font-style: italic;">{$label}</span>
<input type="hidden" id="products_ids_{$row->getData('entity_id')}" value="{$productsIds}">
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
<input type="hidden" id="products_ids_{$row->getData('entity_id')}" value="{$productsIds}">
HTML;
    }

    //########################################

    protected function callbackColumnDescriptionTemplateFilterCallback($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $filteredProductsCategories = array();
        $filteredListingProductsIds = array();

        $categoriesData = $this->getData('categories_data');
        $descriptionTemplatesIds = $this->getData('description_templates_data');

        foreach ($descriptionTemplatesIds as $listingProductId => $descriptionTemplateId) {
            if ($descriptionTemplateId !== NULL) {
                $filteredListingProductsIds[] = $listingProductId;
            }
        }

        foreach ($categoriesData as $categoryId => $listingProducts) {
            foreach ($filteredListingProductsIds as $listingProductId) {
                if (in_array($listingProductId, $listingProducts)) {
                    $filteredProductsCategories[] = $categoryId;
                }
            }
        }

        $filteredProductsCategories = array_unique($filteredProductsCategories);

        if ($value) {
            $collection->addFieldToFilter('entity_id', array('in' => $filteredProductsCategories));
        } else if (!empty($filteredProductsCategories)) {
            $collection->addFieldToFilter('entity_id', array('nin' => $filteredProductsCategories));
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
        if (count($this->getData('categories_data')) === 0) {
            $msg = $this->__('Magento Categories are not specified for Products you are adding.');

            $this->js->add(
<<<JS
    require([
        'M2ePro/Plugin/Messages'
    ],function(MessageObj) {
        MessageObj.clear();
        MessageObj.addErrorMessage('{$msg}');
        $('save_and_go_to_listing_view').addClassName('disabled').onclick = function() {
            return null;
        };
    });
JS
            );

        }

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
<<<JS
    ListingGridHandlerObj.afterInitPage();
JS
            );
        }

        $this->css->add('.grid-listing-column-actions { width:100px; }');

        return parent::_toHtml();
    }

    //########################################

    private function prepareDataByCategories()
    {
        $listingProductsIds = $this->listing->getSetting('additional_data', 'adding_new_asin_listing_products_ids');

        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection()
            ->addFieldToFilter('id',array('in' => $listingProductsIds));

        $productsIds = array();
        $descriptionTemplatesIds = array();
        foreach ($listingProductCollection->getData() as $item) {
            $productsIds[$item['id']] = $item['product_id'];
            $descriptionTemplatesIds[$item['id']] = $item['template_description_id'];
        }
        $productsIds = array_unique($productsIds);

        $categoriesIds = $this->getHelper('Magento\Category')->getLimitedCategoriesByProducts(
            $productsIds,
            $this->listing->getStoreId()
        );

        $categoriesData = array();

        foreach ($categoriesIds as $categoryId) {

            /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
            $collection = $this->magentoProductCollectionFactory->create();
            $collection->addFieldToFilter('entity_id', array('in' => $productsIds));

            $collection->joinTable(
                array('ccp' => $this->resourceConnection->getTableName('catalog_category_product')),
                'product_id=entity_id',
                array('category_id' => 'category_id')
            );
            $collection->addFieldToFilter('category_id', $categoryId);

            $data = $collection->getData();

            foreach ($data as $item) {
                $categoriesData[$categoryId][] = array_search($item['entity_id'], $productsIds);
            }

            $categoriesData[$categoryId] = array_unique($categoriesData[$categoryId]);
        }

        $this->setData('categories_data', $categoriesData);
        $this->setData('description_templates_data', $descriptionTemplatesIds);

        $this->listing->setSetting(
            'additional_data', 'adding_new_asin_description_templates_data', $descriptionTemplatesIds
        );
        $this->listing->save();
    }

    //########################################
}