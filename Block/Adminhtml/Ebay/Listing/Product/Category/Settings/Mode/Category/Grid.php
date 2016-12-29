<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Category;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Category\Grid
{

    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingCategoryGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_category_settings');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');

        $collection->addFieldToFilter(array(
            array('attribute' => 'entity_id', 'in' => array_keys($this->getCategoriesData()))
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

        $category = $this->getHelper('Component\Ebay\Category')
            ->getCategoryTitle(\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN);

        $this->addColumn('ebay_categories', array(
            'header'    => $this->__('eBay Categories'),
            'align'     => 'left',
            'width'     => '*',
            'type'      => 'options',
            'options'   => array(
                //eBay Catalog Primary Category Selected
                1 => $this->__('%1% Selected', $category),
                //eBay Catalog Primary Category Not Selected
                0 => $this->__('%1% Not Selected', $category)
            ),
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnEbayCategories'),
            'filter_condition_callback' => array($this, 'callbackFilterEbayCategories')
        ));

        $this->addColumn('actions', array(
            'header'    => $this->__('Actions'),
            'align'     => 'center',
            'width'     => '100px',
            'type'      => 'text',
            'sortable'  => false,
            'filter'    => false,
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'actions'   => $this->getColumnActionsItems()
        ));

        return parent::_prepareColumns();
    }

    //########################################

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');

        // Set mass-action
        // ---------------------------------------
        $this->getMassactionBlock()->addItem('editCategories', array(
            'label'    => $this->__('Edit All Categories')
        ));

        $this->getMassactionBlock()->addItem('editPrimaryCategories', array(
            'label' => $this->__('Edit eBay Catalog Primary Categories'),
            'url'   => '',
        ));

        if ($this->listing->getAccount()->getChildObject()->getEbayStoreCategories()) {
            $this->getMassactionBlock()->addItem('editStorePrimaryCategories', array(
                'label' => $this->__('Edit Store Catalog Primary Categories'),
                'url'   => '',
            ));
        }
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    public function callbackColumnEbayCategories($value, $row, $column, $isExport)
    {
        $categoriesData = $this->getCategoriesData();
        $categoryTitles = $this->getHelper('Component\Ebay\Category')->getCategoryTitles();

        $html = '';

        $html .= $this->renderEbayCategoryInfo(
            $categoryTitles[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN],
            $categoriesData[$row->getId()],
            'category_main'
        );

        $html .= $this->renderEbayCategoryInfo(
            $categoryTitles[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_SECONDARY],
            $categoriesData[$row->getId()],
            'category_secondary'
        );
        $html .= $this->renderStoreCategoryInfo(
            $categoryTitles[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN],
            $categoriesData[$row->getId()],
            'store_category_main'
        );

        $html .= $this->renderStoreCategoryInfo(
            $categoryTitles[\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_SECONDARY],
            $categoriesData[$row->getId()],
            'store_category_secondary'
        );

        if (empty($html)) {

            $html .= <<<HTML
<span class="icon-warning" style="font-style: italic; color: gray">{$this->__('Not Selected')}</span>
HTML;

        }

        return $html;
    }

    //########################################

    protected function callbackFilterEbayCategories($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $primaryCategory = array('selected' => array(), 'blank' => array());

        foreach ($this->getCategoriesData() as $categoryId => $templateData) {
            if ($templateData['category_main_mode'] != \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
                $primaryCategory['selected'][] = $categoryId;
                continue;
            }

            $primaryCategory['blank'][] = $categoryId;
        }

        if ($value == 0) {
            $collection->addFieldToFilter('entity_id', array('in' => $primaryCategory['blank']));
        } else {
            $collection->addFieldToFilter('entity_id', array('in' => $primaryCategory['selected']));
        }
    }

    //########################################

    protected function renderEbayCategoryInfo($title, $data, $key)
    {
        $info = '';

        if ($data[$key.'_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
            $info = $data[$key.'_path'];
            $info.= '&nbsp;('.$data[$key.'_id'].')';
        } elseif ($data[$key.'_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $info = $this->__(
                'Magento Attribute > %attribute_label%',
                $this->getHelper('Magento\Attribute')->getAttributeLabel(
                    $data[$key.'_attribute'],
                    $this->listing->getStoreId()
                )
            );
        }

        return $this->renderCategoryInfo($title,$info);
    }

    protected function renderStoreCategoryInfo($title, $data, $key)
    {
        $info = '';

        if ($data[$key.'_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY) {
            $info = $data[$key.'_path'];
            $info.= '&nbsp;('.$data[$key.'_id'].')';
        } elseif ($data[$key.'_mode'] == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $info = $this->__(
                'Magento Attribute > %attribute_label%',
                $this->getHelper('Magento\Attribute')->getAttributeLabel(
                    $data[$key.'_attribute'],
                    $this->listing->getStoreId()
                )
            );
        }

        return $this->renderCategoryInfo($title,$info);
    }

    protected function renderCategoryInfo($title, $info)
    {
        if (!$info) {
            return '';
        }

        return <<<HTML
<div>
    <span style="text-decoration: underline">{$title}</span>
    <p style="padding: 2px 0 0 10px;">
        {$info}
    </p>
</div>
HTML;

    }

    //########################################

    protected function getColumnActionsItems()
    {
        $categories = $this->getHelper('Component\Ebay\Category')->getCategoryTitles();

        $actions = array(
            'editCategories' => array(
                'caption' => $this->__('Edit All Categories'),
                'field'   => 'id',
                'onclick_action' => 'EbayListingProductCategorySettingsModeCategoryGridObj.'
                                    .'actions[\'editCategoriesAction\']'
            ),

            'editPrimaryCategories' => array(
                //Edit Primary Category
                'caption' => $this->__('Edit %1%', $categories[
                    \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN
                ]),
                'field'   => 'id',
                'onclick_action' => 'EbayListingProductCategorySettingsModeCategoryGridObj.'
                                    .'actions[\'editPrimaryCategoriesAction\']'
            )
        );

        if ($this->listing->getAccount()->getChildObject()->getEbayStoreCategories()) {
            $actions['editStorePrimaryCategories'] = array(
                'caption' => $this->__('Edit %1%', $categories[
                    \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN
                ]),
                'field'   => 'id',
                'onclick_action' => 'EbayListingProductCategorySettingsModeCategoryGridObj.'
                                    .'actions[\'editStorePrimaryCategoriesAction\']'
            );
        }

        return $actions;
    }

    //########################################

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {

            $this->js->add(
                <<<JS
    EbayListingProductCategorySettingsModeCategoryGridObj.afterInitPage();
JS
            );

            return parent::_toHtml();
        }

        // ---------------------------------------
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions(
            'Ebay\Listing\Product\Category\Settings', array('_current' => true)
        ));

        $this->jsUrl->add($this->getUrl('*/ebay_listing_product_category_settings', array(
            'step' => 3,
            '_current' => true
        )), 'ebay_listing_product_category_settings');
        // ---------------------------------------

        // ---------------------------------------
        $this->jsTranslator->add('Done', $this->__('Done'));
        $this->jsTranslator->add('Set eBay Categories', $this->__('Set eBay Categories'));
        // ---------------------------------------

        // ---------------------------------------
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Ebay\Category')
        );
        // ---------------------------------------

        $disableContinue = '';
        if ($this->getCollection()->getSize() === 0) {
            $disableContinue = <<<JS
$('ebay_listing_category_continue_btn').addClassName('disabled').onclick = function() {
    return null;
};
JS;
        }

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'M2ePro/Ebay/Listing/Product/Category/Settings/Mode/Category/Grid'
    ], function(){
        {$disableContinue}

        EbayListingProductCategorySettingsModeCategoryGridObj =
            new EbayListingProductCategorySettingsModeCategoryGrid('{$this->getId()}');

        EbayListingProductCategorySettingsModeCategoryGridObj.afterInitPage();
    });
JS
    );

        $this->css->add('.grid-listing-column-actions { width:100px; }');

        return parent::_toHtml();
    }

    //########################################
}