<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Category;

use \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\CategoryMode as CategoryModeFilter;
use \Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use \Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

/**
 * @method setCategoriesData()
 * @method getCategoriesData()
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Category\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Category\Grid
{
    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingCategoryGrid');

        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);

        $this->listing = $this->parentFactory->getCachedObjectLoaded(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Listing',
            $this->getRequest()->getParam('id')
        );
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');

        $collection->addFieldToFilter([
            ['attribute' => 'entity_id', 'in' => array_keys($this->getCategoriesData())]
        ]);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('magento_category', [
            'header'    => $this->__('Magento Category'),
            'align'     => 'left',
            'width'     => '500px',
            'type'      => 'text',
            'index'     => 'name',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnMagentoCategory']
        ]);

        $category = $this->getHelper('Component_Ebay_Category')
            ->getCategoryTitle(\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN);

        $this->addColumn('ebay_categories', [
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
            'sortable'       => false,
            'frame_callback' => [$this, 'callbackColumnCategories'],
            'filter_condition_callback' => [$this, 'callbackFilterEbayCategories'],
        ]);

        $this->addColumn('actions', [
            'header'    => $this->__('Actions'),
            'align'     => 'center',
            'width'     => '100px',
            'type'      => 'text',
            'sortable'  => false,
            'filter'    => false,
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'actions'   => $this->getColumnActionsItems()
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');

        $this->getMassactionBlock()->addItem('editCategories', [
            'label' => $this->__('Edit Categories'),
            'url'   => ''
        ]);

        $this->getMassactionBlock()->addItem('resetCategories', [
            'label' => $this->__('Reset Categories'),
            'url'   => '',
        ]);

        return parent::_prepareMassaction();
    }

    //########################################

    public function getRowUrl($row)
    {
        return false;
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
        $renderer->setHideSpecificsRequiredMark(true);
        $renderer->setEntityIdField('entity_id');

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

    protected function getColumnActionsItems()
    {
        return [
            'editCategories' => [
                'caption' => $this->__('Edit Categories'),
                'field'   => 'id',
                'onclick_action' => "EbayListingProductCategorySettingsModeCategoryGridObj."
                                    ."actions['editCategoriesAction']"
            ],

            'resetCategories' => [
                'caption' => $this->__('Reset Categories'),
                'field'   => 'id',
                'onclick_action' => "EbayListingProductCategorySettingsModeCategoryGridObj."
                                    ."actions['resetCategoriesAction']"
            ]
        ];
    }

    //########################################

    protected function _toHtml()
    {
        $categoriesData = $this->getCategoriesData();
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
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions(
            'Ebay_Listing_Product_Category_Settings',
            ['_current' => true]
        ));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay_Category', ['_current' => true]));

        $this->jsUrl->add($this->getUrl('*/ebay_listing_product_category_settings', [
            'step' => 3,
            '_current' => true
        ]), 'ebay_listing_product_category_settings');
        // ---------------------------------------

        // ---------------------------------------
        $this->jsTranslator->add('Set eBay Category', $this->__('Set eBay Category'));
        $this->jsTranslator->add('Category Settings', $this->__('Category Settings'));
        $this->jsTranslator->add('Specifics', $this->__('Specifics'));

        $this->jsTranslator->add('select_relevant_category', $this->__(
            "To proceed, the category data must be specified.
            Please select a relevant Primary eBay Category for at least one product."
        ));

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
            if (isset($categoryData[eBayCategory::TYPE_EBAY_MAIN]) &&
                $categoryData[eBayCategory::TYPE_EBAY_MAIN]['mode'] !== TemplateCategory::CATEGORY_MODE_NONE
            ) {
                if ($categoryData[eBayCategory::TYPE_EBAY_MAIN]['is_custom_template'] !== null) {
                    return true;
                }

                $specificsRequired = $this->getHelper('Component_Ebay_Category_Ebay')->hasRequiredSpecifics(
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
