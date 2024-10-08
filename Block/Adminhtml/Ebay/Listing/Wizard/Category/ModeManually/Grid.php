<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\ModeManually;

use Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\CategoryMode as CategoryModeFilter;
use Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Step as StepResource;
use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product;
use Ess\M2ePro\Model\Listing\Ui\RuntimeStorage as ListingRuntimeStorage;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage as WizardRuntimeStorage;
use Ess\M2ePro\Helper\Component\Ebay\Category;
use Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory;
use Ess\M2ePro\Block\Adminhtml\Magento\Context\Template;
use Ess\M2ePro\Helper\Data as DataHelper;
use Magento\Backend\Helper\Data;

class Grid extends AbstractGrid
{
    private array $categoriesData;

    private Product $productResource;

    private ListingRuntimeStorage $uiListingRuntimeStorage;

    private WizardRuntimeStorage $uiWizardRuntimeStorage;

    private CollectionFactory $magentoProductCollectionFactory;

    private DataHelper $dataHelper;

    private Category $componentEbayCategory;

    private string $gridId;

    public function __construct(
        array $categoriesData,
        WizardRuntimeStorage $uiWizardRuntimeStorage,
        ListingRuntimeStorage $uiListingRuntimeStorage,
        Product $productResource,
        Category $componentEbayCategory,
        CollectionFactory $magentoProductCollectionFactory,
        Template $context,
        Data $backendHelper,
        DataHelper $dataHelper,
        array $data = []
    ) {
        $this->categoriesData = $categoriesData;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->productResource = $productResource;
        $this->uiListingRuntimeStorage = $uiListingRuntimeStorage;
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;
        $this->componentEbayCategory = $componentEbayCategory;
        $this->dataHelper = $dataHelper;

        parent::__construct(
            $context,
            $backendHelper,
            $data
        );
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId($this->gridId = 'listingCategoryManuallyGrid');

        $this->setDefaultSort('product_id');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = $this->magentoProductCollectionFactory
            ->create()
            ->addAttributeToSelect('name');

        $collection->getSelect()->distinct();
        $store = $this->_storeManager->getStore($this->uiListingRuntimeStorage->getListing()->getStoreId());

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

        $collection->joinTable(
            ['listing_product' => $this->productResource->getMainTable()],
            sprintf(
                '%s = entity_id',
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product::COLUMN_MAGENTO_PRODUCT_ID,
            ),
            [
                'product_id' => \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product::COLUMN_ID,

                'listing_product_id' => \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product::COLUMN_ID,
            ],
            sprintf(
                '{{table}}.%s = %s',
                StepResource::COLUMN_WIZARD_ID,
                $this->uiWizardRuntimeStorage->getManager()->getWizardId(),
            ),
        );

        $productAddIds = $this->uiWizardRuntimeStorage->getManager()->getProductsIds();

       // $collection->getSelect()->where('e.entity_id IN (?)', $productAddIds);

        $collection->addFieldToFilter(
            'entity_id',
            ['in' => $productAddIds],
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('listing_product_id', [
            'header' => $this->__('Product ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'filter_index' => 'entity_id',
            'store_id' => $this->uiListingRuntimeStorage->getListing()->getStoreId(),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => $this->__('Product Title'),
            'align' => 'left',
            'width' => '350px',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
        ]);

        $category = $this->componentEbayCategory
            ->getCategoryTitle(\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN);

        $this->addColumn('category', [
            'header' => $this->__('eBay Categories'),
            'align' => 'left',
            'width' => '*',
            'index' => 'category',
            'type' => 'options',
            'filter' => CategoryModeFilter::class,
            'category_type' => eBayCategory::TYPE_EBAY_MAIN,
            'options' => [
                //Primary Category Selected
                CategoryModeFilter::MODE_SELECTED => $this->__('%1% Selected', $category),
                //Primary Category Not Selected
                CategoryModeFilter::MODE_NOT_SELECTED => $this->__('%1% Not Selected', $category),
                //Primary Category Name/ID
                CategoryModeFilter::MODE_TITLE => $this->__('%1% Name/ID', $category),
            ],
            'sortable' => false,
            'filter_condition_callback' => [$this, 'callbackFilterEbayCategories'],
            'frame_callback' => [$this, 'callbackColumnCategories'],
        ]);

        $this->addColumn('actions', [
            'header' => $this->__('Actions'),
            'align' => 'center',
            'width' => '100px',
            'type' => 'text',
            'sortable' => false,
            'filter' => false,
            'field' => 'listing_product_id',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'group_order' => $this->getGroupOrder(),
            'actions' => $this->getColumnActionsItems(),
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('listing_product_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // ---------------------------------------

        $this->getMassactionBlock()->setGroups([
            'edit_settings' => __('Edit Settings'),
            'other' => __('Other'),
        ]);

        // ---------------------------------------

        $this->getMassactionBlock()->addItem('editCategories', [
            'label' => __('Edit Category'),
            'url' => '',
        ], 'edit_settings');

        $this->getMassactionBlock()->addItem('resetCategories', [
            'label' => __('Reset Category'),
            'url' => '',
        ], 'edit_settings');

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

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
            \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\CategoryInfo::class
        );

        $renderer->setColumn($column);

        $renderer->setCategoriesData($this->categoriesData);
        $renderer->setListing($this->uiListingRuntimeStorage->getListing());
        $renderer->setHideUnselectedSpecifics(true);
        $renderer->setEntityIdField('listing_product_id');

        return $renderer->render($row);
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
            'selected' => [],
            'blank' => [],
            'ebay' => [],
            'attribute' => [],
            'path' => [],
        ];

        foreach ($this->getCategoriesData() as $categoryId => $categoryData) {
            if (
                !isset($categoryData[$categoryType]) ||
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

            if (
                !empty($filter['title']) &&
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

    public function getRowUrl($item)
    {
        return false;
    }

    //########################################

    protected function getGroupOrder()
    {
        return [
            'edit_actions' => $this->__('Edit Settings'),
            'other' => $this->__('Other'),
        ];
    }

    protected function getColumnActionsItems()
    {
        $actions = [
            'editCategories' => [
                'caption' => $this->__('Edit Categories'),
                'group' => 'edit_actions',
                'field' => 'id',
                'onclick_action' => "EbayListingProductCategorySettingsModeProductGridObj."
                    . "actions['editCategoriesAction']",
            ],
            //@todo uncomment when respective actions and controllers will be implemented
            //'getSuggestedCategories' => [
            //    'caption' => $this->__('Get Suggested Primary Category'),
            //    'group' => 'other',
            //    'field' => 'id',
            //    'onclick_action' => "EbayListingProductCategorySettingsModeProductGridObj."
            //        . "actions['getSuggestedCategoriesAction']",
            //],
            'resetCategories' => [
                'caption' => $this->__('Reset Categories'),
                'group' => 'other',
                'field' => 'id',
                'onclick_action' => "EbayListingProductCategorySettingsModeProductGridObj."
                    . "actions['resetCategoriesAction']",
            ],
            //'removeItem' => [
            //    'caption' => $this->__('Remove Item'),
            //    'group' => 'other',
            //    'field' => 'id',
            //    'onclick_action' => "EbayListingProductCategorySettingsModeProductGridObj."
            //        . "actions['removeItemAction']",
            //],
        ];

        return $actions;
    }

    //########################################

    protected function _toHtml()
    {
        $allIdsStr = $this->getGridIdsJson();

        $isAlLeasOneCategorySelected = (int)!$this->isAlLeasOneCategorySelected($this->categoriesData);
        $showErrorMessage = (int)!empty($this->categoriesData);

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

        /**
         * Overrides for new listing wizard
         *
         * @todo refactor, remove redundant overrides
         */

        // ---------------------------------------
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions(
                'Ebay_Listing_Product_Category_Settings',
                ['_current' => true]
            )
        );

        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Ebay_Category', ['_current' => true]));

        $this->jsUrl->add(
            $this->getUrl('*/ebay_listing_product_category_settings', ['step' => 3, '_current' => true]),
            'ebay_listing_product_category_settings'
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_category/getChooserEditHtml',
                [
                    'account_id' => $this->uiListingRuntimeStorage->getListing()->getAccountId(),
                    'marketplace_id' => $this->uiListingRuntimeStorage->getListing()->getMarketplaceId(),
                ]
            ),
            'ebay_category/getChooserEditHtml'
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_listing_wizard_category/getChooserBlockHtml',
                [
                    'id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId(),
                ]
            ),
            'ebay_listing_product_category_settings/getChooserBlockHtml'
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_listing_wizard_category/assignModeManually',
                [
                    'id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId(),
                ]
            ),
            'ebay_listing_product_category_settings/stepTwoSaveToSession'
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_listing_wizard_category/validateModeManually',
                [
                'id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId(),
                ],
            ),
            'ebay_listing_product_category_settings/stepTwoModeValidate'
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_listing_wizard_category/CompleteModeManually',
                [
                    'id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId(),
                ],
            ),
            'ebay_listing_product_category_settings'
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_listing_wizard_category/resetModeManually',
                [
                    'id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId(),
                ],
            ),
            'ebay_listing_product_category_settings/stepTwoReset'
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_listing_wizard_category/setCategoryByEbayRecommendation',
                [
                    'id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId(),
                ]
            ),
            'ebay_listing_product_category_settings/stepTwoGetSuggestedCategory'
        );

        /**
         * End of override
         */

        // ---------------------------------------
        $this->jsTranslator->add('Set eBay Category', $this->__('Set eBay Category'));
        $this->jsTranslator->add('Category Settings', $this->__('Category Settings'));
        $this->jsTranslator->add('Specifics', $this->__('Specifics'));

        $this->jsTranslator->add(
            'Suggested Categories were not assigned.',
            $this->__(
                'eBay could not assign Categories for %product_title% Products.'
            )
        );
        $this->jsTranslator->add(
            'Suggested Categories were assigned.',
            $this->__(
                'Suggested Categories were Received for %product_title% Product(s).'
            )
        );

        $this->jsTranslator->add(
            'select_relevant_category',
            $this->__(
                "To proceed, the category data must be specified.
            Please select a relevant Primary eBay Category for at least one product."
            )
        );
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

        $select->columns('listing_product.id');

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
            if (
                isset($categoryData[eBayCategory::TYPE_EBAY_MAIN]) &&
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
}
