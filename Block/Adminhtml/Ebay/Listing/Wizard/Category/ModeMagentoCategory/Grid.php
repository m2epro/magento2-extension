<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\ModeMagentoCategory;

use Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\CategoryMode as CategoryModeFilter;
use Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage as WizardRuntimeStorage;
use Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;
use Ess\M2ePro\Helper\Magento\Category as CategoryHelper;
use Ess\M2ePro\Model\Listing;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Category\Grid
{
    private Listing $listing;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category */
    private $componentEbayCategory;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;

    private array $categoriesData;

    private WizardRuntimeStorage $uiWizardRuntimeStorage;

    private CategoryHelper $categoryHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Ess\M2ePro\Model\ResourceModel\Magento\Category\CollectionFactory $categoryCollectionFactory,
        CategoryHelper $categoryHelper,
        Listing $listing,
        WizardRuntimeStorage $uiWizardRuntimeStorage,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $categoriesData,
        array $data = []
    ) {
        parent::__construct(
            $categoryCollectionFactory,
            $context,
            $backendHelper,
            $dataHelper,
            $data
        );

        $this->componentEbayCategory = $componentEbayCategory;
        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        $this->categoriesData = $categoriesData;
        $this->listing = $listing;
        $this->uiWizardRuntimeStorage = $uiWizardRuntimeStorage;
        $this->categoryHelper = $categoryHelper;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingCategoryGrid');

        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');
        $magentoProductIds = $this->uiWizardRuntimeStorage->getManager()->getProductsIds();
        $magentoCategoryIds = $this->categoryHelper->getLimitedCategoriesByProducts($magentoProductIds);

        $collection->addFieldToFilter([
            ['attribute' => 'entity_id', 'in' => $magentoCategoryIds],
        ]);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('magento_category', [
            'header' => $this->__('Magento Category'),
            'align' => 'left',
            'width' => '500px',
            'type' => 'text',
            'index' => 'name',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnMagentoCategory'],
        ]);

        $category = $this->componentEbayCategory
            ->getCategoryTitle(\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN);

        $this->addColumn('ebay_categories', [
            'header' => $this->__('eBay Categories'),
            'align' => 'left',
            'width' => '*',
            'type' => 'options',
            'filter' => CategoryModeFilter::class,
            'category_type' => eBayCategory::TYPE_EBAY_MAIN,
            'index' => 'category',
            'options' => [
                //Primary Category Selected
                CategoryModeFilter::MODE_SELECTED => $this->__('%1% Selected', $category),
                //Primary Category Not Selected
                CategoryModeFilter::MODE_NOT_SELECTED => $this->__('%1% Not Selected', $category),
                //Primary Category Name/ID
                CategoryModeFilter::MODE_TITLE => $this->__('%1% Name/ID', $category),
            ],
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnCategories'],
            'filter_condition_callback' => [$this, 'callbackFilterEbayCategories'],
        ]);

        $this->addColumn('actions', [
            'header' => $this->__('Actions'),
            'align' => 'center',
            'width' => '100px',
            'type' => 'text',
            'sortable' => false,
            'filter' => false,
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'actions' => $this->getColumnActionsItems(),
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');

        $this->getMassactionBlock()->addItem('editCategories', [
            'label' => $this->__('Edit Categories'),
            'url' => '',
        ]);

        $this->getMassactionBlock()->addItem('resetCategories', [
            'label' => $this->__('Reset Categories'),
            'url' => '',
        ]);

        return parent::_prepareMassaction();
    }

    //########################################

    public function getRowUrl($item)
    {
        return false;
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
        $renderer->setListing($this->listing);
        $renderer->setHideSpecificsRequiredMark(true);
        $renderer->setEntityIdField('entity_id');

        return $renderer->render($row);
    }

    //########################################

    protected function callbackFilterEbayCategories($collection, $column)
    {
        $filter = $column->getFilter()->getValue();

        if (empty($this->categoriesData) && $filter['mode'] == CategoryModeFilter::MODE_NOT_SELECTED) {
            return;
        }

        $categoryType = $column->getData('category_type');

        if ($filter == null) {
            return;
        }

        $categoryStat = [
            'path' => [],
        ];

        foreach ($this->categoriesData as $categoryId => $categoryData) {
            if (
                !empty($filter['title']) &&
                (strpos($categoryData[$categoryType]['path'], $filter['title']) !== false ||
                    strpos($categoryData[$categoryType]['value'], $filter['title']) !== false)
            ) {
                $categoryStat['path'][] = $categoryId;
            }
        }

        if ($filter['mode'] == CategoryModeFilter::MODE_TITLE) {
            $ids = $categoryStat['path'];
        } else {
            $ids = array_keys($this->categoriesData);
        }

        if ($filter['mode'] == CategoryModeFilter::MODE_NOT_SELECTED) {
            $condition = 'nin';
        } else {
            $condition = 'in';
        }

        $collection->addFieldToFilter('entity_id', [$condition => $ids]);
    }

    //########################################

    protected function getColumnActionsItems()
    {
        return [
            'editCategories' => [
                'caption' => $this->__('Edit Categories'),
                'field' => 'id',
                'onclick_action' => "EbayListingProductCategorySettingsModeCategoryGridObj."
                    . "actions['editCategoriesAction']",
            ],

            'resetCategories' => [
                'caption' => $this->__('Reset Categories'),
                'field' => 'id',
                'onclick_action' => "EbayListingProductCategorySettingsModeCategoryGridObj."
                    . "actions['resetCategoriesAction']",
            ],
        ];
    }

    //########################################

    protected function _toHtml()
    {
        $categoriesData = $this->categoriesData;
        $isAlLeasOneCategorySelected = (int)!$this->isAlLeasOneCategorySelected($categoriesData);
        $showErrorMessage = (int)!empty($categoriesData);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    EbayListingProductCategorySettingsModeCategoryGridObj.afterInitPage();
    EbayListingProductCategorySettingsModeCategoryGridObj.validateCategories(
            '{$isAlLeasOneCategorySelected}', '{$showErrorMessage}'
        );
JS
            );

            return parent::_toHtml();
        }

        // ---------------------------------------
        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions(
                'Ebay_Listing_Product_Category_Settings',
                ['_current' => true]
            )
        );
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Ebay_Category', ['_current' => true]));

        /**
         * @todo refactor
         *
         * Hardcoded overrides to make grid js components use proper (new wizard) controllers paths
         */
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
                '*/ebay_listing_wizard_category/assignModeCategory',
                [
                    'id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId(),
                ]
            ),
            'ebay_listing_product_category_settings/stepTwoSaveToSession'
        );

        $this->jsUrl->add(
            $this->getUrl(
                '*/ebay_listing_wizard_category/resetModeCategory',
                [
                    'id' => $this->uiWizardRuntimeStorage->getManager()->getWizardId(),
                ]
            ),
            'ebay_listing_product_category_settings/stepTwoReset'
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

        /**
         * End of hardcoded overrides
         */

        $this->jsTranslator->add('Set eBay Category', $this->__('Set eBay Category'));
        $this->jsTranslator->add('Category Settings', $this->__('Category Settings'));
        $this->jsTranslator->add('Specifics', $this->__('Specifics'));

        $this->jsTranslator->add(
            'select_relevant_category',
            $this->__(
                "To proceed, the category data must be specified.
            Please select a relevant Primary eBay Category for at least one product."
            )
        );

        // ---------------------------------------

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'M2ePro/Ebay/Listing/Product/Category/Settings/Mode/Category/Grid'
    ], function(){

        window.EbayListingProductCategorySettingsModeCategoryGridObj =
            new EbayListingProductCategorySettingsModeCategoryGrid('{$this->getId()}');

        EbayListingProductCategorySettingsModeCategoryGridObj.afterInitPage();

        EbayListingProductCategorySettingsModeCategoryGridObj.validateCategories(
            '{$isAlLeasOneCategorySelected}', '{$showErrorMessage}'
        );
    });
JS
        );

        $this->css->add('.grid-listing-column-actions { width:100px; }');

        return parent::_toHtml();
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
                if ($categoryData[eBayCategory::TYPE_EBAY_MAIN]['is_custom_template'] !== null) {
                    return true;
                }

                $specificsRequired = $this->componentEbayCategoryEbay->hasRequiredSpecifics(
                    $categoryData[eBayCategory::TYPE_EBAY_MAIN]['value'],
                    $this->listing->getMarketplaceId()
                );

                if (!$specificsRequired) {
                    return true;
                }
            }
        }

        return false;
    }

    //########################################
}
