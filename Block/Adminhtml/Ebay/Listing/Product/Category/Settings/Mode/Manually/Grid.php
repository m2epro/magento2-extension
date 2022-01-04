<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Manually;

use Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\CategoryMode as CategoryModeFilter;
use Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use \Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

/**
 * @method setCategoriesData()
 * @method getCategoriesData()
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Manually\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing = null;

    protected $magentoProductCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingCategoryManuallyGrid');

        $this->listing = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Listing',
            $this->getRequest()->getParam('id')
        );

        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    //########################################

    protected function _prepareCollection()
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create()
            ->addAttributeToSelect('name');

        $collection->getSelect()->distinct();
        $store = $this->_storeManager->getStore($this->listing->getData('store_id'));

        if ($store->getId()) {
            $collection->joinAttribute(
                'custom_name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'thumbnail',
                'catalog_product/thumbnail',
                'entity_id',
                null,
                'left',
                $store->getId()
            );
        } else {
            $collection->addAttributeToSelect('thumbnail');
        }

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            [
                'id' => 'id'
            ],
            '{{table}}.listing_id='.(int)$this->listing->getId()
        );
        $elpTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['elp' => $elpTable],
            'listing_product_id=id',
            [
                'listing_product_id' => 'listing_product_id'
            ]
        );

        $productAddIds = (array)$this->getHelper('Data')->jsonDecode(
            $this->listing->getChildObject()->getData('product_add_ids')
        );

        $collection->getSelect()->where('lp.id IN (?)', $productAddIds);

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'   => $this->__('Product ID'),
            'align'    => 'right',
            'width'    => '100px',
            'type'     => 'number',
            'index'    => 'entity_id',
            'filter_index' => 'entity_id',
            'store_id' => $this->listing->getStoreId(),
            'renderer' => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId'
        ]);

        $this->addColumn('name', [
            'header'    => $this->__('Product Title'),
            'align'     => 'left',
            'width'     => '350px',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'name',
            'escape'    => false
        ]);

        $category = $this->getHelper('Component_Ebay_Category')
            ->getCategoryTitle(\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN);

        $this->addColumn('category', [
            'header'    => $this->__('eBay Categories'),
            'align'     => 'left',
            'width'     => '*',
            'type'      => 'options',
            'filter'    => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\CategoryMode',
            'category_type' => eBayCategory::TYPE_EBAY_MAIN,
            'options'   => [
                //Primary Category Selected
                CategoryModeFilter::MODE_SELECTED     => $this->__('%1% Selected', $category),
                //Primary Category Not Selected
                CategoryModeFilter::MODE_NOT_SELECTED => $this->__('%1% Not Selected', $category),
                //Primary Category Name/ID
                CategoryModeFilter::MODE_TITLE        => $this->__('%1% Name/ID', $category)
            ],
            'sortable'  => false,
            'filter_condition_callback' => [$this, 'callbackFilterEbayCategories'],
            'frame_callback' => [$this, 'callbackColumnCategories']
        ]);

        $this->addColumn('actions', [
            'header'    => $this->__('Actions'),
            'align'     => 'center',
            'width'     => '100px',
            'type'      => 'text',
            'sortable'  => false,
            'filter'    => false,
            'field'     => 'listing_product_id',
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'group_order' => $this->getGroupOrder(),
            'actions'   => $this->getColumnActionsItems()
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('listing_product_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // ---------------------------------------

        $this->getMassactionBlock()->setGroups([
            'edit_settings'         => $this->__('Edit Settings'),
            'other'                 => $this->__('Other')
        ]);

        // ---------------------------------------
        $this->getMassactionBlock()->addItem('editCategories', [
            'label' => $this->__('Edit Categories'),
            'url'   => '',
        ], 'edit_settings');

        $this->getMassactionBlock()->addItem('getSuggestedCategories', [
            'label' => $this->__('Get Suggested Primary Categories'),
            'url'   => '',
        ], 'other');

        $this->getMassactionBlock()->addItem('resetCategories', [
            'label' => $this->__('Reset Categories'),
            'url'   => '',
        ], 'other');

        $this->getMassactionBlock()->addItem('removeItem', [
             'label' => $this->__('Remove Item(s)'),
             'url'   => '',
        ], 'other');
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {
            if ($column->getId() == 'websites') {
                $this->getCollection()->joinField(
                    'websites',
                    'catalog_product_website',
                    'website_id',
                    'product_id=entity_id',
                    null,
                    'left'
                );
            }
        }
        return parent::_addColumnFilterToCollection($column);
    }

    //########################################

    public function callbackColumnCategories($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\CategoryInfo $renderer */
        $renderer = $this->getLayout()->getBlockSingleton(
            'Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\CategoryInfo'
        );

        $renderer->setColumn($column);
        $renderer->setCategoriesData($this->getCategoriesData());
        $renderer->setListing($this->listing);
        $renderer->setHideUnselectedSpecifics(true);
        $renderer->setEntityIdField('listing_product_id');

        return  $renderer->render($row);
    }

    //########################################

    protected function callbackFilterEbayCategories($collection, $column)
    {
        $filter = $column->getFilter()->getValue();
        $categoryType = $column->getData('category_type');

        if ($filter == null || $categoryType === null) {
            return;
        }

        $categoryStat = [
            'selected'  => [],
            'blank'     => [],
            'ebay'      => [],
            'attribute' => [],
            'path'      => []
        ];

        foreach ($this->getCategoriesData() as $categoryId => $categoryData) {
            if (!isset($categoryData[$categoryType]) ||
                $categoryData[$categoryType]['mode'] == TemplateCategory::CATEGORY_MODE_NONE
            ) {
                $categoryStat['blank'][] = $categoryId;
                continue;
            }

            $categoryStat['selected'][] = $categoryId;

            if ($categoryData[$categoryType]['mode'] == TemplateCategory::CATEGORY_MODE_EBAY) {
                $categoryStat['ebay'][] = $categoryId;
            }

            if ($categoryData[$categoryType]['mode'] == TemplateCategory::CATEGORY_MODE_ATTRIBUTE) {
                $categoryStat['attribute'][] = $categoryId;
            }

            if (!empty($filter['title']) &&
                (strpos($categoryData[$categoryType]['path'], $filter['title']) !== false ||
                    strpos($categoryData[$categoryType]['value'], $filter['title']) !== false)
            ) {
                $categoryStat['path'][] = $categoryId;
            }
        }

        $ids = [];
        $filter['mode'] == CategoryModeFilter::MODE_NOT_SELECTED && $ids = $categoryStat['blank'];
        $filter['mode'] == CategoryModeFilter::MODE_SELECTED && $ids = $categoryStat['selected'];
        $filter['mode'] == CategoryModeFilter::MODE_EBAY && $ids = $categoryStat['ebay'];
        $filter['mode'] == CategoryModeFilter::MODE_ATTRIBUTE && $ids = $categoryStat['attribute'];
        $filter['mode'] == CategoryModeFilter::MODE_TITLE && $ids = $categoryStat['path'];

        $collection->addFieldToFilter('entity_id', ['in' => $ids]);
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing_product_category_settings/StepTwoModeManuallyGrid', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function getGroupOrder()
    {
        return [
            'edit_actions' => $this->__('Edit Settings'),
            'other'        => $this->__('Other')
        ];
    }

    protected function getColumnActionsItems()
    {
        $actions = [
            'editCategories' => [
                'caption' => $this->__('Edit Categories'),
                'group'   => 'edit_actions',
                'field'   => 'id',
                'onclick_action' => "EbayListingProductCategorySettingsModeProductGridObj."
                                    ."actions['editCategoriesAction']"
            ],
            'getSuggestedCategories' => [
                'caption' => $this->__('Get Suggested Primary Category'),
                'group'   => 'other',
                'field'   => 'id',
                'onclick_action' => "EbayListingProductCategorySettingsModeProductGridObj."
                                    ."actions['getSuggestedCategoriesAction']"
            ],
            'resetCategories' => [
                'caption' => $this->__('Reset Categories'),
                'group'   => 'other',
                'field'   => 'id',
                'onclick_action' => "EbayListingProductCategorySettingsModeProductGridObj."
                                    ."actions['resetCategoriesAction']"
            ],
            'removeItem' => [
                'caption' => $this->__('Remove Item'),
                'group'   => 'other',
                'field'   => 'id',
                'onclick_action' => "EbayListingProductCategorySettingsModeProductGridObj."
                                    ."actions['removeItemAction']"
            ],
        ];

        return $actions;
    }

    //########################################

    protected function _toHtml()
    {
        $allIdsStr = $this->getGridIdsJson();

        $categoriesData = $this->getCategoriesData();
        $isAlLeasOneCategorySelected = (int)!$this->isAlLeasOneCategorySelected($categoriesData);
        $showErrorMessage = (int)!empty($categoriesData);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    EbayListingProductCategorySettingsModeProductGridObj.afterInitPage();
    EbayListingProductCategorySettingsModeProductGridObj.getGridMassActionObj().setGridIds('{$allIdsStr}');
    EbayListingProductCategorySettingsModeProductGridObj.validateCategories(
        '{$isAlLeasOneCategorySelected}', '{$showErrorMessage}'
    );
JS
            );

            return parent::_toHtml();
        }

        // ---------------------------------------
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions(
            'Ebay_Listing_Product_Category_Settings',
            ['_current' => true]
        ));

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay_Category', ['_current' => true]));

        $this->jsUrl->add(
            $this->getUrl('*/ebay_listing_product_category_settings', ['step' => 3, '_current' => true]),
            'ebay_listing_product_category_settings'
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_category/getChooserEditHtml',
                [
                    'account_id' => $this->listing->getAccountId(),
                    'marketplace_id' => $this->listing->getMarketplaceId()
                ]
            ),
            'ebay_category/getChooserEditHtml'
        );
        // ---------------------------------------

        // ---------------------------------------
        $this->jsTranslator->add('Set eBay Category', $this->__('Set eBay Category'));
        $this->jsTranslator->add('Category Settings', $this->__('Category Settings'));
        $this->jsTranslator->add('Specifics', $this->__('Specifics'));

        $this->jsTranslator->add('Suggested Categories were not assigned.', $this->__(
            'eBay could not assign Categories for %product_title% Products.'
        ));
        $this->jsTranslator->add('Suggested Categories were assigned.', $this->__(
            'Suggested Categories were Received for %product_title% Product(s).'
        ));

        $this->jsTranslator->add('select_relevant_category', $this->__(
            "To proceed, the category data must be specified.
            Please select a relevant Primary eBay Category for at least one product."
        ));
        // ---------------------------------------

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->js->addOnReadyJs(
                <<<JS
require([
    'M2ePro/Plugin/ProgressBar',
    'M2ePro/Plugin/AreaWrapper',
    'M2ePro/Ebay/Listing/Product/Category/Settings/Mode/Product/Grid',
    'M2ePro/Ebay/Listing/Product/Category/Settings/Mode/Product/SuggestedSearch'
], function(){

    window.EbayListingProductCategorySettingsModeProductGridObj
            = new EbayListingProductCategorySettingsModeProductGrid('{$this->getId()}');

    window.WrapperObj = new AreaWrapper('products_container');
    window.ProgressBarObj = new ProgressBar('products_progress_bar');

    EbayListingProductCategorySettingsModeProductGridObj.afterInitPage();
    EbayListingProductCategorySettingsModeProductGridObj.getGridMassActionObj().setGridIds('{$allIdsStr}');
    EbayListingProductCategorySettingsModeProductGridObj.validateCategories(
        '{$isAlLeasOneCategorySelected}', '{$showErrorMessage}'
    );

    window.EbayListingProductCategorySettingsModeProductSuggestedSearchObj
            = new EbayListingProductCategorySettingsModeProductSuggestedSearch();

        {$this->additionalJs()}
})
JS
            );
        }

        return parent::_toHtml();
    }

    //########################################

    private function getGridIdsJson()
    {
        $select = clone $this->getCollection()->getSelect();
        $select->reset(\Magento\Framework\DB\Select::ORDER);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $select->resetJoinLeft();

        $select->columns('elp.listing_product_id');

        $connection = $this->getCollection()->getConnection();

        return implode(',', $connection->fetchCol($select));
    }

    //########################################

    protected function isAlLeasOneCategorySelected($categoriesData)
    {
        if (empty($categoriesData)) {
            return false;
        }

        foreach ($categoriesData as $productId => $categoryData) {
            if (isset($categoryData[eBayCategory::TYPE_EBAY_MAIN]) &&
                $categoryData[eBayCategory::TYPE_EBAY_MAIN]['mode'] !== TemplateCategory::CATEGORY_MODE_NONE
            ) {
                return true;
            }
        }

        return false;
    }

    //########################################

    protected function additionalJs()
    {
        return '';
    }

    //########################################
}
