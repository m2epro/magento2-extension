<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Specific;

use Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;
use \Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;

/**
 * @method setCategoriesData()
 * @method getCategoriesData()
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Category\Settings\Specific\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    const SPECIFICS_MODE_NOT_SET_REQUIRED     = 'not-set-required';
    const SPECIFICS_MODE_NOT_SET_NOT_REQUIRED = 'not-set-not-required';
    const SPECIFICS_MODE_DEFAULT              = 'default';
    const SPECIFICS_MODE_CUSTOM               = 'custom';

    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $customCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->customCollectionFactory = $customCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingCategorySpecificGrid');

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
        $collection = $this->customCollectionFactory->create();

        foreach ($this->getCategoriesData() as $hash => $data) {
            $row = $data[eBayCategory::TYPE_EBAY_MAIN];

            if (!isset($row['is_custom_template'])) {
                $specificsRequired = $this->getHelper('Component_Ebay_Category_Ebay')->hasRequiredSpecifics(
                    $row['value'],
                    $this->listing->getMarketplaceId()
                );

                $spMode = $specificsRequired
                    ? self::SPECIFICS_MODE_NOT_SET_REQUIRED
                    : self::SPECIFICS_MODE_NOT_SET_NOT_REQUIRED;
            } elseif ($row['is_custom_template'] == 1) {
                $spMode = self::SPECIFICS_MODE_CUSTOM;
            } else {
                $spMode = self::SPECIFICS_MODE_DEFAULT;
            }

            $row['id'] = $hash;
            $row['specifics_mode'] = $spMode;
            $row['full_path'] = $row['path'] .' '. $row['value'];

            $collection->addItem(new \Magento\Framework\DataObject($row));
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('category', [
            'header'   => $this->__('eBay Primary Category'),
            'align'    => 'left',
            'width'    => '*',
            'index'    => 'full_path',
            'filter_condition_callback' => [$this, 'callbackFilterCategory'],
            'frame_callback'            => [$this, 'callbackColumnCategory']
        ]);

        $this->addColumn('specifics', [
            'header'    => $this->__('Item Specifics'),
            'align'     => 'left',
            'width'     => '400',
            'type'      => 'options',
            'index'     => 'specifics_mode',
            'options'   => [
                self::SPECIFICS_MODE_NOT_SET_REQUIRED     => $this->__('Not Set (required)'),
                self::SPECIFICS_MODE_NOT_SET_NOT_REQUIRED => $this->__('Not Set (not required)'),
                self::SPECIFICS_MODE_DEFAULT              => $this->__('Default'),
                self::SPECIFICS_MODE_CUSTOM               => $this->__('Custom'),
            ],
            'filter_condition_callback' => [$this, 'callbackFilterSpecifics'],
            'frame_callback' => [$this, 'callbackColumnSpecifics']
        ]);

        $this->addColumn('actions', [
            'header'    => $this->__('Actions'),
            'align'     => 'center',
            'width'     => '150px',
            'type'      => 'action',
            'index'     => 'actions',
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'sortable'  => false,
            'filter'    => false,
            'no_link'   => true,
            'actions'   => [
                'editSpecifics' => [
                    'caption'        => $this->__('Edit'),
                    'field'          => 'id',
                    'onclick_action' => "EbayListingProductCategorySettingsSpecificGridObj."
                                        ."actions['editSpecificsAction']"
                ],
            ]
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnCategory($value, $row, $column, $isExport)
    {
        if ($row['mode'] == TemplateCategory::CATEGORY_MODE_EBAY) {
            return "{$row['path']}&nbsp;({$row['value']})";
        } elseif ($row['mode'] == TemplateCategory::CATEGORY_MODE_ATTRIBUTE) {
            return $row['path'];
        }

        return '';
    }

    public function callbackColumnSpecifics($value, $row, $column, $isExport)
    {
        if ($row['specifics_mode'] === self::SPECIFICS_MODE_NOT_SET_REQUIRED) {
            return <<<HTML
<span style="font-style: italic; color: red;">{$this->__('Not Set')}</span>
HTML;
        } elseif ($row['specifics_mode'] === self::SPECIFICS_MODE_NOT_SET_NOT_REQUIRED) {
            return <<<HTML
<span style="font-style: italic; color: grey;">{$this->__('Not Set')}</span>
HTML;
        } elseif ($row['specifics_mode'] === self::SPECIFICS_MODE_CUSTOM) {
            return "<span>{$this->__('Custom')}</span>";
        } elseif ($row['specifics_mode'] === self::SPECIFICS_MODE_DEFAULT) {
            return "<span>{$this->__('Default')}</span>";
        }

        return '';
    }

    //########################################

    protected function callbackFilterCategory($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $this->getCollection()->addFilter(
            'full_path',
            $value,
            \Ess\M2ePro\Model\ResourceModel\Collection\Custom::CONDITION_LIKE
        );
    }

    protected function callbackFilterSpecifics($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $this->getCollection()->addFilter(
            'specifics_mode',
            $value,
            \Ess\M2ePro\Model\ResourceModel\Collection\Custom::CONDITION_MATCH
        );
    }

    //########################################

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function _toHtml()
    {
        $categoriesData = $this->getCategoriesData();
        $isAllSelected = (int)!$this->isAllSpecificsSelected();
        $showErrorMessage = (int)!empty($categoriesData);

        $categoriesData = $this->getHelper('Data')->jsonEncode($categoriesData);
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    EbayListingProductCategorySettingsSpecificGridObj.setCategoriesData({$categoriesData});

    EbayListingProductCategorySettingsModeProductGridObj.afterInitPage();
    EbayListingProductCategorySettingsModeProductGridObj.validateCategories(
        '{$isAllSelected}', '{$showErrorMessage}'
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
            $this->getUrl('*/ebay_listing_product_category_settings/save', ['_current' => true]),
            'ebay_listing_product_category_settings'
        );
        // ---------------------------------------

        // ---------------------------------------
        $this->jsTranslator->add('Specifics', $this->__('Specifics'));
        $this->jsTranslator->add('select_relevant_category', $this->__(
            'To proceed, Category data must be specified.
             Ensure you set Item Specifics for all assigned Categories.'
        ));
        // ---------------------------------------

        $this->jsPhp->addConstants($this->getHelper('Data')
            ->getClassConstants(\Ess\M2ePro\Helper\Component\Ebay\Category::class));

        if (!$this->getRequest()->isXmlHttpRequest()) {
            $this->js->addOnReadyJs(
                <<<JS
require([
    'M2ePro/Ebay/Listing/Product/Category/Settings/Mode/Product/Grid',
    'M2ePro/Ebay/Listing/Product/Category/Settings/Specific/Grid'
], function(){

    window.EbayListingProductCategorySettingsModeProductGridObj
            = new EbayListingProductCategorySettingsModeProductGrid('{$this->getId()}');

    window.EbayListingProductCategorySettingsSpecificGridObj
            = new EbayListingProductCategorySettingsSpecificGrid('{$this->getId()}');

    EbayListingProductCategorySettingsSpecificGridObj.setMarketplaceId({$this->listing->getMarketplaceId()});
    EbayListingProductCategorySettingsSpecificGridObj.setCategoriesData({$categoriesData});

    EbayListingProductCategorySettingsModeProductGridObj.afterInitPage();
    EbayListingProductCategorySettingsModeProductGridObj.validateCategories(
            '{$isAllSelected}', '{$isAllSelected}'
        );
})
JS
            );
        }

        return parent::_toHtml();
    }

    //########################################

    protected function isAllSpecificsSelected()
    {
        foreach ($this->getCategoriesData() as $productId => $categoryData) {
            if ($categoryData[eBayCategory::TYPE_EBAY_MAIN]['is_custom_template'] === null) {
                $specificsRequired = $this->getHelper('Component_Ebay_Category_Ebay')->hasRequiredSpecifics(
                    $categoryData[eBayCategory::TYPE_EBAY_MAIN]['value'],
                    $this->listing->getMarketplaceId()
                );

                if ($specificsRequired) {
                    return false;
                }
            }
        }

        return true;
    }

    //########################################
}
