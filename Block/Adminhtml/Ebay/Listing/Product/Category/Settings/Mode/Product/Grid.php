<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Mode\Product;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var \Ess\M2ePro\Model\Listing */
    private $listing = NULL;

    protected $magentoProductCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingCategoryProductGrid');
        // ---------------------------------------

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('listing_for_products_category_settings');

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareCollection()
    {
        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create()
            ->addAttributeToSelect('name');

        // ---------------------------------------
        $collection->getSelect()->distinct();
        // ---------------------------------------

        // Set filter store
        // ---------------------------------------
        $store = $this->_storeManager->getStore($this->listing->getData('store_id'));

        if ($store->getId()) {
            $collection->joinAttribute(
                'custom_name',
                'catalog_product/name',
                'entity_id',
                NULL,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'thumbnail',
                'catalog_product/thumbnail',
                'entity_id',
                NULL,
                'left',
                $store->getId()
            );
        } else {
            $collection->addAttributeToSelect('thumbnail');
        }
        // ---------------------------------------

        // ---------------------------------------
        $productAddIds = (array)$this->getHelper('Data')->jsonDecode(
            $this->listing->getChildObject()->getData('product_add_ids')
        );

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            array('lp' => $lpTable),
            'product_id=entity_id',
            array(
                'id' => 'id'
            ),
            '{{table}}.listing_id='.(int)$this->listing->getId()
        );
        $elpTable = $this->activeRecordFactory->getObject('Ebay\Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            array('elp' => $elpTable),
            'listing_product_id=id',
            array(
                'listing_product_id' => 'listing_product_id'
            )
        );

        $collection->getSelect()->where('lp.id IN (?)', $productAddIds);
        // ---------------------------------------

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
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
            'header'    => $this->__('Product Title'),
            'align'     => 'left',
            'width'     => '350px',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'name',
            'frame_callback' => array($this, 'callbackColumnProductTitle')
        ));

        $category = $this->getHelper('Component\Ebay\Category')
            ->getCategoryTitle(\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN);

        $this->addColumn('category', array(
            'header'    => $this->__('eBay Categories'),
            'align'     => 'left',
            'width'     => '*',
            'type'      => 'options',
            'index'     => 'category',
            'filter_index' => 'entity_id',
            'options'   => array(
                //eBay Catalog Primary Category Selected
                1 => $this->__('%1% Selected', $category),
                //eBay Catalog Primary Category Not Selected
                0 => $this->__('%1% Not Selected', $category)
            ),
            'frame_callback' => array($this, 'callbackColumnCategoryCallback'),
            'filter_condition_callback' => array($this, 'callbackColumnCategoryFilterCallback')
        ));

        $this->addColumn('actions', array(
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
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('listing_product_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // ---------------------------------------

        $this->getMassactionBlock()->setGroups(array(
            'edit_settings'         => $this->__('Edit Settings'),
            'other'                 => $this->__('Other')
        ));

        // ---------------------------------------
        $this->getMassactionBlock()->addItem('editCategories', array(
            'label' => $this->__('All Categories'),
            'url'   => '',
        ), 'edit_settings');

        $this->getMassactionBlock()->addItem('editPrimaryCategories', array(
            'label' => $this->__('eBay Catalog Primary Categories'),
            'url'   => '',
        ), 'edit_settings');

        if ($this->listing->getAccount()->getChildObject()->getEbayStoreCategories()) {
            $this->getMassactionBlock()->addItem('editStorePrimaryCategories', array(
                'label' => $this->__('Store Catalog Primary Categories'),
                'url'   => '',
            ), 'edit_settings');
        }

        $this->getMassactionBlock()->addItem('getSuggestedCategories', array(
            'label' => $this->__('Get Suggested From eBay'),
            'url'   => '',
        ), 'other');

        $this->getMassactionBlock()->addItem('resetCategories', array(
            'label' => $this->__('Reset eBay Categories'),
            'url'   => '',
        ), 'other');

        $this->getMassactionBlock()->addItem('removeItem', array(
             'label'    => $this->__('Remove Products'),
             'url'      => '',
        ), 'other');
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
                    NULL,
                    'left'
                );
            }
        }
        return parent::_addColumnFilterToCollection($column);
    }

    //########################################

    public function callbackColumnProductId($value, $row, $column, $isExport)
    {
        $productId = (int)$row->getData('entity_id');
        $storeId = (int)$this->listing->getData('store_id');

        $url = $this->getUrl('catalog/product/edit', array('id' => $productId));
        $htmlWithoutThumbnail = '<a href="' . $url . '" target="_blank">'.$productId.'</a>';

        $showProductsThumbnails = (bool)(int)$this->getHelper('Module')->getConfig()
            ->getGroupValue('/view/','show_products_thumbnails');

        if (!$showProductsThumbnails) {
            return $htmlWithoutThumbnail;
        }

        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProductId($productId);
        $magentoProduct->setStoreId($storeId);

        $thumbnail = $magentoProduct->getThumbnailImage();
        if (is_null($thumbnail)) {
            return $htmlWithoutThumbnail;
        }

        $thumbnailUrl = $thumbnail->getUrl();

        return <<<HTML
<a href="{$url}" target="_blank">
    {$productId}
    <div style="margin-top: 5px">
        <img style="max-width: 100px; max-height: 100px;" src="{$thumbnailUrl}" />
    </div>
</a>
HTML;
    }

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        return '<span>' . $this->getHelper('Data')->escapeHtml($value) . '</span>';
    }

    public function callbackColumnCategoryCallback($value, $row, $column, $isExport)
    {
        $productId   = $row->getData('listing_product_id');
        $sessionData = $this->getHelper('Data\Session')->getValue(
            'ebay_listing_product_category_settings/mode_product'
        );

        $html = '';

        if ($sessionData[$productId]['category_main_mode']) {
            $categoryType = \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN;
            $categoryMode = $sessionData[$productId]['category_main_mode'];
            $categoryAttribute = $sessionData[$productId]['category_main_attribute'];
            $categoryId = $sessionData[$productId]['category_main_id'];
            $categoryPath = $sessionData[$productId]['category_main_path'];

            $html .= $this->renderCategory(
                $categoryType,
                $categoryMode,
                $categoryAttribute,
                $categoryId,
                $categoryPath
            );
        }

        if ($sessionData[$productId]['category_secondary_mode']) {
            $categoryType = \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_SECONDARY;
            $categoryMode = $sessionData[$productId]['category_secondary_mode'];
            $categoryAttribute = $sessionData[$productId]['category_secondary_attribute'];
            $categoryId = $sessionData[$productId]['category_secondary_id'];
            $categoryPath = $sessionData[$productId]['category_secondary_path'];

            if ($html != '') {
                $html .= '<br/>';
            }

            $html .= $this->renderCategory(
                $categoryType,
                $categoryMode,
                $categoryAttribute,
                $categoryId,
                $categoryPath
            );
        }

        if ($sessionData[$productId]['store_category_main_mode']) {
            $categoryType = \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_MAIN;
            $categoryMode = $sessionData[$productId]['store_category_main_mode'];
            $categoryAttribute = $sessionData[$productId]['store_category_main_attribute'];
            $categoryId = $sessionData[$productId]['store_category_main_id'];
            $categoryPath = $sessionData[$productId]['store_category_main_path'];

            if ($html != '') {
                $html .= '<br/>';
            }

            $html .= $this->renderStoreCategory(
                $categoryType,
                $categoryMode,
                $categoryAttribute,
                $categoryId,
                $categoryPath
            );
        }

        if ($sessionData[$productId]['store_category_secondary_mode']) {
            $categoryType = \Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_STORE_SECONDARY;
            $categoryMode = $sessionData[$productId]['store_category_secondary_mode'];
            $categoryAttribute = $sessionData[$productId]['store_category_secondary_attribute'];
            $categoryId = $sessionData[$productId]['store_category_secondary_id'];
            $categoryPath = $sessionData[$productId]['store_category_secondary_path'];

            if ($html != '') {
                $html .= '<br/>';
            }

            $html .= $this->renderStoreCategory(
                $categoryType,
                $categoryMode,
                $categoryAttribute,
                $categoryId,
                $categoryPath
            );
        }

        if ($html == '') {
            $label = $this->__('Not Selected');

            $html .= <<<HTML
<span class="icon-warning" style="color: gray; font-style: italic;">{$label}</span>
HTML;
        }

        return $html;
    }

    private function getCategoryTypeName($categoryType)
    {
        $categoryTitles = $this->getHelper('Component\Ebay\Category')->getCategoryTitles();

        return '<span style="text-decoration: underline;">'.$categoryTitles[$categoryType].'</span>';
    }

    private function renderCategory($categoryType, $mode, $attribute, $id, $path)
    {
        $info = '';

        switch ($mode) {
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY:
                $info = $this->getCategoryPathLabel($path, $id);
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE:
                $info = $this->getCategoryAttributeLabel($attribute);
                break;
        }

        if (!$info) {
            return '';
        }

        $categoryTypeName = $this->getCategoryTypeName($categoryType);

        return <<<HTML
{$categoryTypeName}<br/>
{$info}
HTML;
    }

    private function renderStoreCategory($categoryType, $mode, $attribute, $id, $path)
    {
        $info = '';

        switch ($mode) {
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_EBAY:
                $info = $this->getCategoryPathLabel($path, $id);
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE:
                $info = $this->getCategoryAttributeLabel($attribute);
                break;
        }

        if (!$info) {
            return '';
        }

        $categoryTypeName = $this->getCategoryTypeName($categoryType);

        return <<<HTML
{$categoryTypeName}<br/>
{$info}
HTML;
    }

    private function getCategoryAttributeLabel($attributeCode)
    {
        $attributeLabel = $this->getHelper('Magento\Attribute')->getAttributeLabel(
            $attributeCode,
            $this->listing->getData('store_id')
        );

        $result = $this->__('Magento Attribute') . '&nbsp;->&nbsp;';
        $result .= $this->getHelper('Data')->escapeHtml($attributeLabel);

        return '<span style="padding-left: 10px; display: inline-block;">' . $result . '</span>';
    }

    private function getCategoryPathLabel($categoryPath, $categoryId = NULL)
    {
        $result = $categoryPath;

        if ($categoryId) {
            $result .= '&nbsp;(' . $categoryId . ')';
        }

        return '<div style="padding-left: 10px; display: inline-block;">' . $result . '</div>';
    }

    //########################################

    protected function callbackColumnCategoryFilterCallback($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $sessionKey = 'ebay_listing_product_category_settings';
        $sessionData = $this->getHelper('Data\Session')->getValue($sessionKey);

        $primaryCategory = array('selected' => array(), 'blank' => array());
        foreach ($sessionData['mode_product'] as $listingProductId => $listingProductData) {
            if ($listingProductData['category_main_mode'] !=
                    \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE) {
                $primaryCategory['selected'][] = $listingProductId;
                continue;
            }
            $primaryCategory['blank'][] = $listingProductId;
        }

        if ($value == 0) {
            $collection->addFieldToFilter('listing_product_id', array('in' => $primaryCategory['blank']));
        } else {
            $collection->addFieldToFilter('listing_product_id', array('in' => $primaryCategory['selected']));
        }
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/stepTwoModeProductGrid', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################
    protected function getGroupOrder()
    {
        return array(
            'edit_actions'     => $this->__('Edit Settings'),
            'other'            => $this->__('Other'),
        );
    }

    protected function getColumnActionsItems()
    {
        $actions = array(
            'getSuggestedCategories' => array(
                'caption' => $this->__('Get Suggested From eBay'),
                'group'   => 'other',
                'field' => 'id',
                'onclick_action' => 'EbayListingProductCategorySettingsModeProductGridObj.'
                                    .'actions[\'getSuggestedCategoriesAction\']'
            ),
            'editCategories' => array(
                'caption' => $this->__('All Categories'),
                'group'   => 'edit_actions',
                'field' => 'id',
                'onclick_action' => 'EbayListingProductCategorySettingsModeProductGridObj.'
                    .'actions[\'editCategoriesAction\']'
            ),
            'editPrimaryCategories' => array(
                'caption' => $this->__('eBay Catalog Primary Categories'),
                'group'   => 'edit_actions',
                'field' => 'id',
                'onclick_action' => 'EbayListingProductCategorySettingsModeProductGridObj.'
                                    .'actions[\'editPrimaryCategoriesAction\']'
            )
        );

        if ($this->listing->getAccount()->getChildObject()->getEbayStoreCategories()) {
            $actions['editStorePrimaryCategories'] = array(
                'caption' => $this->__('Store Catalog Primary Categories'),
                'group'   => 'edit_actions',
                'field' => 'id',
                'onclick_action' => 'EbayListingProductCategorySettingsModeProductGridObj.'
                                    .'actions[\'editStorePrimaryCategoriesAction\']'
            );
        }

        $actions = array_merge($actions, array(
            'resetCategories' => array(
                'caption' => $this->__('Reset eBay Categories'),
                'group'   => 'other',
                'field' => 'id',
                'onclick_action' => 'EbayListingProductCategorySettingsModeProductGridObj.'
                                    .'actions[\'resetCategoriesAction\']'
            ),
            'removeItem' => array(
                'caption' => $this->__('Remove Products'),
                'group'   => 'other',
                'field' => 'id',
                'onclick_action' => 'EbayListingProductCategorySettingsModeProductGridObj.'
                                    .'actions[\'removeItemAction\']'
            ),
        ));

        return $actions;
    }

    //########################################

    protected function _toHtml()
    {
        $allIdsStr = $this->getGridIdsJson();

        if ($this->getRequest()->isXmlHttpRequest()) {

            $this->js->addOnReadyJs(
                <<<JS
    EbayListingProductCategorySettingsModeProductGridObj.afterInitPage();
    EbayListingProductCategorySettingsModeProductGridObj.getGridMassActionObj().setGridIds('{$allIdsStr}');
JS
            );

            return parent::_toHtml();
        }

        // ---------------------------------------
        $this->jsUrl->addUrls(
            $this->getHelper('Data')->getControllerActions(
                'Ebay\Listing\Product\Category\Settings', array('_current' => true)
            )
        );

        $this->jsUrl->add(
            $this->getUrl('*/ebay_listing_product_category_settings', array('step' => 3, '_current' => true)),
            'ebay_listing_product_category_settings'
        );

        $this->jsUrl->add(
            $this->getUrl('*/ebay_category/getChooserEditHtml',
                array(
                    'account_id' => $this->listing->getAccountId(),
                    'marketplace_id' => $this->listing->getMarketplaceId()
                )
            ),
            'ebay_category/getChooserEditHtml'
        );
        // ---------------------------------------

        // ---------------------------------------
        $translations = array();
        // M2ePro_TRANSLATIONS
        // You have not selected the Primary eBay Category for some Products.
        $text = 'You have not selected the Primary eBay Category for some Products.';
        $translations[$text] = $this->__($text);
        // M2ePro_TRANSLATIONS
        // Are you sure?
        $text = 'Are you sure?';
        $translations[$text] = $this->__($text);
        // M2ePro_TRANSLATIONS
        // eBay could not assign Categories for %product_tite% Products.
        $text = 'eBay could not assign Categories for %product_title% Products.';
        $translations[$text] = $this->__($text);
        // M2ePro_TRANSLATIONS
        // Suggested Categories were successfully Received for %product_title% Product(s).
        $text = 'Suggested Categories were successfully Received for %product_title% Product(s).';
        $translations[$text] = $this->__($text);
        // M2ePro_TRANSLATIONS
        // Set eBay Category
        $text = 'Set eBay Category';
        $translations[$text] = $this->__($text);
        // M2ePro_TRANSLATIONS
        // Set eBay Category for Product(s)
        $text = 'Set eBay Category for Product(s)';
        $translations[$text] = $this->__($text);
        // M2ePro_TRANSLATIONS
        // Set eBay Catalog Primary Category for Product(s)
        $text = 'Set eBay Catalog Primary Category for Product(s)';
        $translations[$text] = $this->__($text);

        $this->jsTranslator->addTranslations($translations);
        // ---------------------------------------

        // ---------------------------------------
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Helper\Component\Ebay\Category')
        );
        // ---------------------------------------

        $getSuggested = $this->getHelper('Data')->jsonEncode(
            (bool)$this->getHelper('Data\GlobalData')->getValue('get_suggested')
        );

        $this->js->addOnReadyJs(
            <<<JS
require([
    'M2ePro/Ebay/Listing/Product/Category/Settings/Mode/Product/Grid',
    'M2ePro/Ebay/Listing/Product/Category/Settings/Mode/Product/SuggestedSearch',
    'M2ePro/Plugin/ProgressBar',
    'M2ePro/Plugin/AreaWrapper'
], function(){

    window.WrapperObj = new AreaWrapper('products_container');
    window.ProgressBarObj = new ProgressBar('products_progress_bar');

    window.EbayListingProductCategorySettingsModeProductGridObj
            = new EbayListingProductCategorySettingsModeProductGrid('{$this->getId()}');
    EbayListingProductCategorySettingsModeProductSuggestedSearchObj
            = new EbayListingProductCategorySettingsModeProductSuggestedSearch();

    EbayListingProductCategorySettingsModeProductGridObj.afterInitPage();
    EbayListingProductCategorySettingsModeProductGridObj.getGridMassActionObj().setGridIds('{$allIdsStr}');

    if ({$getSuggested}) {
        EbayListingProductCategorySettingsModeProductGridObj.getSuggestedCategoriesForAll();
    }
})
JS
        );

        return parent::_toHtml();
    }

    //########################################

    private function getGridIdsJson()
    {
        $select = clone $this->getCollection()->getSelect();
        $select->reset(\Zend_Db_Select::ORDER);
        $select->reset(\Zend_Db_Select::LIMIT_COUNT);
        $select->reset(\Zend_Db_Select::LIMIT_OFFSET);
        $select->reset(\Zend_Db_Select::COLUMNS);
        $select->resetJoinLeft();

        $select->columns('elp.listing_product_id');

        $connection = $this->getCollection()->getConnection('core_read');

        return implode(',',$connection->fetchCol($select));
    }

    //########################################
}