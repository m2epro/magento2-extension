<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Amazon;

use Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;
use Ess\M2ePro\Model\Listing\Log;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\View\Amazon\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Grid
{
    private $lockedDataCache = [];

    private $childProductsWarningsData;
    private $parentAndChildReviseScheduledCache = [];

    private $hideSwitchToIndividualConfirm;
    private $hideSwitchToParentConfirm;

    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;

    protected $magentoProductCollectionFactory;
    protected $amazonFactory;
    protected $localeCurrency;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->amazonFactory = $amazonFactory;
        $this->localeCurrency = $localeCurrency;
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setDefaultSort(false);

        $this->listing = $this->getHelper('Data\GlobalData')->getValue('view_listing');

        $this->hideSwitchToIndividualConfirm =
            $this->listing->getSetting('additional_data', 'hide_switch_to_individual_confirm', 0);

        $this->hideSwitchToParentConfirm =
            $this->listing->getSetting('additional_data', 'hide_switch_to_parent_confirm', 0);

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingViewGrid'.$this->listing['id']);
        // ---------------------------------------

        $this->showAdvancedFilterProductsOption = false;
    }

    //########################################

    protected function _prepareCollection()
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->setListingProductModeOn();
        $collection->setStoreId($this->listing->getStoreId());
        $collection->setListing($this->listing->getId());

        $collection
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->joinStockItem();

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            [
                'id'              => 'id',
                'amazon_status'   => 'status',
                'component_mode'  => 'component_mode',
                'additional_data' => 'additional_data'
            ],
            [
                'listing_id' => (int)$this->listing['id']
            ]
        );

        $alpTable = $this->activeRecordFactory->getObject('Amazon_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['alp' => $alpTable],
            'listing_product_id=id',
            [
                'general_id'                     => 'general_id',
                'general_id_search_info'         => 'general_id_search_info',
                'search_settings_status'         => 'search_settings_status',
                'search_settings_data'           => 'search_settings_data',
                'variation_child_statuses'       => 'variation_child_statuses',
                'amazon_sku'                     => 'sku',
                'online_qty'                     => 'online_qty',
                'online_regular_price'           => 'online_regular_price',
                'online_regular_sale_price'      => 'IF(
                  `alp`.`online_regular_sale_price_start_date` IS NOT NULL AND
                  `alp`.`online_regular_sale_price_end_date` IS NOT NULL AND
                  `alp`.`online_regular_sale_price_end_date` >= CURRENT_DATE(),
                  `alp`.`online_regular_sale_price`,
                  NULL
                )',
                'online_regular_sale_price_start_date'   => 'online_regular_sale_price_start_date',
                'online_regular_sale_price_end_date'     => 'online_regular_sale_price_end_date',
                'online_business_price'          => 'online_business_price',
                'online_business_discounts'      => 'online_business_discounts',
                'is_repricing'                   => 'is_repricing',
                'is_afn_channel'                 => 'is_afn_channel',
                'is_general_id_owner'            => 'is_general_id_owner',
                'is_variation_parent'            => 'is_variation_parent',
                'defected_messages'              => 'defected_messages',
                'variation_parent_afn_state'       => 'variation_parent_afn_state',
                'variation_parent_repricing_state' => 'variation_parent_repricing_state',
            ],
            '{{table}}.variation_parent_id is NULL'
        );

        $alprTable = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing')
            ->getResource()->getMainTable();
        $collection->getSelect()->joinLeft(
            ['malpr' => $alprTable],
            '(`alp`.`listing_product_id` = `malpr`.`listing_product_id`)',
            [
                'is_repricing_disabled' => 'is_online_disabled',
                'is_repricing_inactive' => 'is_online_inactive',
            ]
        );

        if ($this->isFilterOrSortByPriceIsUsed('online_price', 'amazon_online_price')) {
            $collection->joinIndexerParent();
        } else {
            $collection->setIsNeedToInjectPrices(true);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        $collection = $this->amazonFactory->getObject('Listing_Product')->getCollection();
        $collection->getSelect()->join(
            ['lps' => $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')
                ->getResource()->getMainTable()],
            'lps.listing_product_id=main_table.id',
            []
        );

        $collection->addFieldToFilter('is_variation_parent', 0);
        $collection->addFieldToFilter('variation_parent_id', ['in' => $this->getCollection()->getColumnValues('id')]);
        $collection->addFieldToFilter('lps.action_type', \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'variation_parent_id' => 'second_table.variation_parent_id',
                'count'               => new \Zend_Db_Expr('COUNT(lps.id)')
            ]
        );
        $collection->getSelect()->group('variation_parent_id');

        foreach ($collection->getItems() as $item) {
            $this->parentAndChildReviseScheduledCache[$item->getData('variation_parent_id')] = true;
        }

        return parent::_afterLoadCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'   => $this->__('Product ID'),
            'align'    => 'right',
            'width'    => '100px',
            'type'     => 'number',
            'index'    => 'entity_id',
            'store_id' => $this->listing->getStoreId(),
            'renderer' => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId'
        ]);

        $this->addColumn('name', [
            'header'    => $this->__('Product Title / Product SKU'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index'   => 'name',
            'escape'         => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('amazon_sku', [
            'header'       => $this->__('SKU'),
            'align'        => 'left',
            'width'        => '150px',
            'type'         => 'text',
            'index'        => 'amazon_sku',
            'filter_index' => 'amazon_sku',
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer\Sku'
        ]);

        $this->addColumn('general_id', [
            'header'         => $this->__('ASIN / ISBN'),
            'align'          => 'left',
            'width'          => '140px',
            'type'           => 'text',
            'index'          => 'general_id',
            'filter_index'   => 'general_id',
            'filter'         => '\Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\GeneralId',
            'frame_callback' => [$this, 'callbackColumnGeneralId'],
            'filter_condition_callback' => [$this, 'callbackFilterGeneralId']
        ]);

        $this->addColumn('online_qty', [
            'header'       => $this->__('QTY'),
            'align'        => 'right',
            'width'        => '70px',
            'type'         => 'number',
            'index'        => 'online_qty',
            'filter_index' => 'online_qty',
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer\Qty',
            'filter'       => 'Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Qty',
            'filter_condition_callback' => [$this, 'callbackFilterQty']
        ]);

        $dir = $this->getParam($this->getVarNameDir(), $this->_defaultDir);

        if ($dir == 'desc') {
            $priceSortField = 'max_online_price';
        } else {
            $priceSortField = 'min_online_price';
        }

        $priceColumn = [
            'header'                    => $this->__('Price'),
            'align'                     => 'right',
            'width'                     => '110px',
            'type'                      => 'number',
            'index'                     => $priceSortField,
            'filter_index'              => $priceSortField,
            'frame_callback'            => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice']
        ];

        if ($this->getHelper('Component_Amazon_Repricing')->isEnabled() &&
            $this->listing->getAccount()->getChildObject()->isRepricing()) {
            $priceColumn['filter'] = 'Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Price';
        }

        $this->addColumn('online_price', $priceColumn);

        $this->addColumn('status', [
            'header'       => $this->__('Status'),
            'width'        => '155px',
            'index'        => 'amazon_status',
            'filter_index' => 'amazon_status',
            'type'         => 'options',
            'sortable'     => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN => $this->__('Unknown'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Inactive (Blocked)')
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
            'filter_condition_callback' => [$this, 'callbackFilterStatus']
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);
        // ---------------------------------------

        // Set mass-action
        // ---------------------------------------
        $groups = [
            'actions'            => $this->__('Actions'),
            'asin_isbn'          => $this->__('ASIN / ISBN'),
            'description_policy' => $this->__('Description Policy'),
            'other'              => $this->__('Other'),
        ];

        $this->getMassactionBlock()->setGroups($groups);

        $this->getMassactionBlock()->addItem('list', [
            'label'    => $this->__('List Item(s)'),
            'url'      => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('revise', [
            'label'    => $this->__('Revise Item(s)'),
            'url'      => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('relist', [
            'label'    => $this->__('Relist Item(s)'),
            'url'      => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('stop', [
            'label'    => $this->__('Stop Item(s)'),
            'url'      => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('stopAndRemove', [
            'label'    => $this->__('Stop on Channel / Remove from Listing'),
            'url'      => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('deleteAndRemove', [
            'label'    => $this->__('Remove from Channel & Listing'),
            'url'      => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('assignGeneralId', [
            'label'    => $this->__('Search Automatically'),
            'url'      => ''
        ], 'asin_isbn');

        $this->getMassactionBlock()->addItem('newGeneralId', [
            'label'    => $this->__('Assign Settings for New ASIN/ISBN'),
            'url'      => '',
        ], 'asin_isbn');

        $this->getMassactionBlock()->addItem('unassignGeneralId', [
            'label'    => $this->__('Reset Information'),
            'url'      => ''
        ], 'asin_isbn');
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        $productTitle = $this->getHelper('Data')->escapeHtml($productTitle);

        $value = '<span>'.$productTitle.'</span>';

        $sku = $this->modelFactory->getObject('Magento\Product')
            ->setProductId($row->getData('entity_id'))
            ->getSku();

        $value .= '<br/><strong>'.$this->__('SKU') .
            ':</strong> '.$this->getHelper('Data')->escapeHtml($sku) . '<br/>';

        $listingProductId = (int)$row->getData('id');
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        if (!$listingProduct->getChildObject()->getVariationManager()->isVariationProduct()) {
            return $value;
        }

        $generalId = $row->getData('general_id');

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        if ($variationManager->isRelationParentType()) {
            $productAttributes = (array)$variationManager->getTypeModel()->getProductAttributes();
            $virtualProductAttributes = $variationManager->getTypeModel()->getVirtualProductAttributes();
            $virtualChannelAttributes = $variationManager->getTypeModel()->getVirtualChannelAttributes();

            $value .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin-left: 7px"><br/>';
            $attributesStr = '';
            if (empty($virtualProductAttributes) && empty($virtualChannelAttributes)) {
                $attributesStr = implode(', ', $productAttributes);
            } else {
                foreach ($productAttributes as $attribute) {
                    if (in_array($attribute, array_keys($virtualProductAttributes))) {
                        $attributesStr .= '<span style="border-bottom: 2px dotted grey">' . $attribute .
                            ' (' . $virtualProductAttributes[$attribute] . ')</span>, ';
                    } elseif (in_array($attribute, array_keys($virtualChannelAttributes))) {
                        $attributesStr .= '<span>' . $attribute .
                            ' (' . $virtualChannelAttributes[$attribute] . ')</span>, ';
                    } else {
                        $attributesStr .= $attribute . ', ';
                    }
                }
                $attributesStr = rtrim($attributesStr, ', ');
            }
            $value .= $attributesStr;

            if (empty($generalId) && !$amazonListingProduct->isGeneralIdOwner() &&
                !empty($productAttributes) && $variationManager->getTypeModel()->isActualProductAttributes()) {
                $popupTitle = $this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml(
                    $this->__('Manage Magento Product Variations')
                ));

                $linkTitle = $this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml(
                    $this->__('Change "Magento Variations" Mode')
                ));

                $iconSettingsPath = $this->getViewFileUrl('Ess_M2ePro::images/settings.png');

                $switchToIndividualJsMethod = <<<JS
AmazonListingProductVariationObj
    .setListingProductId({$listingProductId})
    .showSwitchToIndividualModePopUp('{$popupTitle}');
JS;

                if ($this->hideSwitchToIndividualConfirm) {
                    $switchToIndividualJsMethod = <<<JS
AmazonListingProductVariationObj
    .setListingProductId({$listingProductId})
        .showManagePopup('{$popupTitle}');
JS;
                }

                $value .= <<<HTML
&nbsp;
<a  href="javascript:"
    class="amazon-listing-view-switch-variation-mode"
    onclick="{$switchToIndividualJsMethod}"
    title="{$linkTitle}">
</a>
HTML;
            }

            $value .= '</div>';

            if (!empty($generalId) || $amazonListingProduct->isGeneralIdOwner()) {
                /** @var ParentRelation $parentType */
                $parentType = $variationManager->getTypeModel();

                $linkContent = $this->__('Manage Variations');
                $vpmt = $this->getHelper('Data')->escapeJs(
                    $this->__('Manage Variations of "'. $productTitle . '" ')
                );

                if (!empty($generalId)) {
                    $vpmt .= '('. $generalId .')';
                }

                $problemStyle = '';
                $problemIcon = '';

                $linkTitle = $this->__('Open Manage Variations Tool');

                if (empty($generalId) && $amazonListingProduct->isGeneralIdOwner()) {
                    if (!$parentType->hasChannelTheme() || !$parentType->hasMatchedAttributes()) {
                        $linkTitle = $this->__('Action Required');
                        $problemStyle = 'style="font-weight: bold; color: #FF0000;" ';
                        $iconPath = $this->getViewFileUrl('Ess_M2ePro::images/error.png');
                        $problemIcon = '<img style="vertical-align: middle;" src="'
                            . $iconPath . '" title="' . $linkTitle . '" alt="" width="16" height="16">';
                    }
                } elseif (!empty($generalId)) {
                    if (!$parentType->hasMatchedAttributes()) {
                        $linkTitle = $this->__('Action Required');
                        $problemStyle = 'style="font-weight: bold;color: #FF0000;" ';
                        $iconPath = $this->getViewFileUrl('Ess_M2ePro::images/error.png');
                        $problemIcon = '<img style="vertical-align: middle;" src="'
                            . $iconPath . '" title="' . $linkTitle . '" alt="" width="16" height="16">';
                    } elseif (($listingProduct->getChildObject()->isGeneralIdOwner() &&
                              !$parentType->hasChannelTheme()) ||
                              $this->hasChildWithWarning($listingProductId)) {
                        $linkTitle = $this->__('Action Required');
                        $problemStyle = 'style="font-weight: bold;" ';
                        $iconPath = $this->getViewFileUrl('Ess_M2ePro::images/warning.png');
                        $problemIcon = '<img style="vertical-align: middle;" src="'
                            . $iconPath . '" title="' . $linkTitle . '" alt="" width="16" height="16">';
                    }
                }

                $value .= <<<HTML
<div style="float: left; margin: 0 0 0 7px">
    <a {$problemStyle}href="javascript:"
    onclick="ListingGridObj.variationProductManageHandler.openPopUp(
            {$listingProductId}, '{$this->getHelper('Data')->escapeHtml($vpmt)}'
        )"
    title="{$linkTitle}">{$linkContent}</a>&nbsp;{$problemIcon}
</div>
HTML;
                if ($childListingProductIds = $this->getRequest()->getParam('child_listing_product_ids')) {
                    $this->js->add(<<<JS
    (function() {

         Event.observe(window, 'load', function() {
             ListingGridObj.variationProductManageHandler.openPopUp(
                {$listingProductId}, '{$vpmt}', 'searched_by_child', '{$childListingProductIds}'
             );
         });
    })();
JS
                    );
                }
            }

            return $value;
        }

        $productOptions = $variationManager->getTypeModel()->getProductOptions();

        if (!empty($productOptions)) {
            $value .= '<div style="font-size: 11px; color: grey; margin-left: 7px"><br/>';
            foreach ($productOptions as $attribute => $option) {
                !$option && $option = '--';
                $value .= '<strong>' . $this->getHelper('Data')->escapeHtml($attribute) .
                    '</strong>:&nbsp;' . $this->getHelper('Data')->escapeHtml($option) . '<br/>';
            }
            $value .= '</div>';
        }

        // ---------------------------------------
        $hasInActionLock = $this->getLockedData($row);
        $hasInActionLock = $hasInActionLock['in_action'];
        // ---------------------------------------

        if (!$hasInActionLock) {
            $popupTitle = $this->__('Manage Magento Product Variation');
            $linkTitle  = $this->__('Edit Variation');

            $value .= <<<HTML
<div style="clear: both"></div>
<div style="margin: 0 0 0 7px; float: left;">
    <a  href="javascript:"
        class="amazon-listing-view-edit-variation"
        onclick="AmazonListingProductVariationObj
            .setListingProductId({$listingProductId})
            .showEditPopup('{$popupTitle}');"
        title="{$linkTitle}"></a>
</div>
HTML;
        }

        $popupTitle = $this->__('Manage Magento Product Variations');
        $linkTitle  = $this->__('Add Another Variation(s)');

        $value.= <<<HTML
<div style="margin: 0 0 0 7px; float: left;">
    <a  href="javascript:"
        class="amazon-listing-view-add-variation"
        onclick="AmazonListingProductVariationObj
            .setListingProductId({$listingProductId})
            .showManagePopup('{$popupTitle}');"
        title="{$linkTitle}"></a>
</div>
HTML;

        if (empty($generalId) && !$amazonListingProduct->isGeneralIdOwner()) {
            $linkTitle = $this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml(
                $this->__('Change "Magento Variations" Mode')
            ));

            $switchToParentJsMethod = <<<JS
AmazonListingProductVariationObj
    .setListingProductId({$listingProductId})
        .showSwitchToParentModePopUp('{$popupTitle}');
JS;

            if ($this->hideSwitchToParentConfirm) {
                $switchToParentJsMethod = <<<JS
AmazonListingProductVariationObj
    .setListingProductId({$listingProductId})
        .resetListingProductVariation();
JS;
            }

            $value .= <<<HTML
<div style="margin: 0 0 0 7px; float: left;">
    <a href="javascript:"
        class="amazon-listing-view-switch-variation-mode"
        onclick="{$switchToParentJsMethod}"
        title="{$linkTitle}"></a>
</div>
HTML;
        }

        return $value;
    }

    public function callbackColumnGeneralId($generalId, $row, $column, $isExport)
    {
        if (empty($generalId)) {
            if ($row->getData('is_general_id_owner') == 1) {
                return $this->getGeneralIdColumnValueGeneralIdOwner($row);
            }
            return $this->getGeneralIdColumnValueEmptyGeneralId($row);
        }

        return $this->getGeneralIdColumnValueNotEmptyGeneralId($row);
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ($row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED) {
            return $this->__('N/A');
        }

        if ((!$row->getData('is_variation_parent') &&
            $row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) ||
            ($row->getData('is_variation_parent') && $row->getData('general_id') == '')) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $listingProductId = (int)$row->getData('id');

        $repricingHtml ='';

        if ($this->getHelper('Component_Amazon_Repricing')->isEnabled() && $row->getData('is_repricing')) {
            if ($row->getData('is_variation_parent')) {
                $additionalData = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));

                $repricingManagedCount = isset($additionalData['repricing_managed_count'])
                    ? $additionalData['repricing_managed_count'] : null;

                $repricingNotManagedCount = isset($additionalData['repricing_not_managed_count'])
                    ? $additionalData['repricing_not_managed_count'] : null;

                if ($repricingManagedCount && $repricingNotManagedCount) {
                    $icon = 'repricing-enabled-disabled';
                    $countHtml = '['.$repricingManagedCount.'/'.$repricingNotManagedCount.']';
                    $text = $this->__(
                        'Some Child Products of this Parent ASIN are disabled or unable to be repriced
                        on Amazon Repricing Tool.<br>
                        <strong>Note:</strong> the Price values shown in the grid may differ from Amazon ones.
                        It is caused by some delay in data synchronization between M2E Pro and Repricing Tool.'
                    );
                } elseif ($repricingManagedCount) {
                    $icon = 'enabled';
                    $countHtml = '['.$repricingManagedCount.']';
                    $text = $this->__(
                        'All Child Products of this Parent are Enabled for dynamic repricing. <br>
                        <strong>Please note</strong> that the Price value(s) shown in the grid might be different
                        from the actual one from Amazon. It is caused by the delay in the values updating
                        made via the Repricing Service.'
                    );
                } elseif ($repricingNotManagedCount) {
                    $icon = 'repricing-disabled';
                    $countHtml = '['.$repricingNotManagedCount.']';
                    $text = $this->__(
                        'The Child Products of this Parent ASIN are disabled or
                                                       unable to be repriced on Amazon Repricing Tool.'
                    );
                } else {
                    $icon = 'repricing-enabled';
                    $countHtml = $this->__('[show]');
                    $text = $this->__(
                        'Some Child Products of this Parent are managed by the Repricing Service. <br>
                        <strong>Please note</strong> that the Price value(s) shown in the grid might be
                        different from the actual one from Amazon. It is caused by the delay in the
                        values updating made via the Repricing Service.'
                    );
                }

                $filter = base64_encode('online_price[is_repricing]=1');

                $productTitle = $this->getHelper('Data')->escapeHtml($row->getData('name'));
                $vpmt = $this->__('Manage Variations of &quot;%s%&quot; ', $productTitle);
                // @codingStandardsIgnoreLine
                $vpmt = addslashes($vpmt);

                $generalId = $row->getData('general_id');
                if (!empty($generalId)) {
                    $vpmt .= '('. $generalId .')';
                }

                $linkTitle = $this->__('Show Child Products managed by Amazon Repricing Service.');

                $repricingHtml = <<<HTML
<div>
    <div class="fix-magento-tooltip {$icon}">
        {$this->getTooltipHtml($text)}
    </div>
    <a href="javascript:void(0)"
       style="vertical-align: top;line-height: 20px; padding-left: 5px;"
       class="hover-underline"
       title="{$linkTitle}"
       onclick="ListingGridObj.variationProductManageHandler.openPopUp(
        {$listingProductId}, '{$vpmt}', '{$filter}'
    )">$countHtml</a>
</div>
HTML;
            } elseif (!$row->getData('is_variation_parent')) {
                $icon = 'repricing-enabled';
                $text = $this->__(
                    'This Product is used by Amazon Repricing Tool, so its Price cannot be managed via M2E Pro.<br>
                    <strong>Please note</strong> that the Price value shown in the grid might be different
                    from the actual one from Amazon. It is caused by the delay in the values
                    updating made via the Repricing Service.'
                );

                if ((int)$row->getData('is_repricing_disabled') == 1 ||
                    (int)$row->getData('is_repricing_inactive') == 1) {
                    $icon = 'repricing-disabled';
                    $text = $this->__(
                        'This Item is disabled or unable to be repriced on Amazon Repricing Tool.
                        Its Price is updated via M2E Pro.'
                    );
                }

                $repricingHtml = <<<HTML
&nbsp;<div class="fix-magento-tooltip {$icon}">
    {$this->getTooltipHtml($text)}
</div>
HTML;
            }
        }

        $onlineMinRegularPrice = (float)$row->getData('min_online_regular_price');
        $onlineMaxRegularPrice = (float)$row->getData('max_online_regular_price');

        $onlineMinBusinessPrice = (float)$row->getData('min_online_business_price');
        $onlineMaxBusinessPrice = (float)$row->getData('max_online_business_price');

        if (empty($onlineMinRegularPrice) && empty($onlineMinBusinessPrice)) {
            if ($row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED ||
                $row->getData('is_variation_parent')
            ) {
                return $this->__('N/A') . $repricingHtml;
            } else {
                return '<i style="color:gray;">receiving...</i>' . $repricingHtml;
            }
        }

        $currency = $this->listing->getMarketplace()->getChildObject()->getDefaultCurrency();

        if ($row->getData('is_variation_parent')) {
            $onlineRegularPriceStr = '<span style="color: #f00;">0</span>';
            if (!empty($onlineMinRegularPrice) && !empty($onlineMaxRegularPrice)) {
                $onlineMinRegularPriceStr = $this->convertAndFormatPriceCurrency($onlineMinRegularPrice, $currency);
                $onlineMaxRegularPriceStr = $this->convertAndFormatPriceCurrency($onlineMaxRegularPrice, $currency);

                $onlineRegularPriceStr = $onlineMinRegularPriceStr
                    .(($onlineMinRegularPrice != $onlineMaxRegularPrice)?' - '.$onlineMaxRegularPriceStr:'');
            }

            $onlineBusinessPriceStr = '';
            if (!empty($onlineMinBusinessPrice) && !empty($onlineMaxBusinessPrice)) {
                $onlineMinBusinessPriceStr = $this->convertAndFormatPriceCurrency($onlineMinBusinessPrice, $currency);
                $onlineMaxBusinessPriceStr = $this->convertAndFormatPriceCurrency($onlineMaxBusinessPrice, $currency);

                $onlineBusinessPriceStr = '<br /><strong>B2B: </strong>'.$onlineMinBusinessPriceStr
                    .(($onlineMinBusinessPrice != $onlineMaxBusinessPrice)?' - '.$onlineMaxBusinessPriceStr:'');
            }

            return $onlineRegularPriceStr.$onlineBusinessPriceStr.$repricingHtml;
        }

        $onlineRegularPrice = $row->getData('online_regular_price');
        if ((float)$onlineRegularPrice <= 0) {
            $regularPriceValue = '<span style="color: #f00;">0</span>';
        } else {
            $regularPriceValue = $this->convertAndFormatPriceCurrency($onlineRegularPrice, $currency);
        }

        if ($row->getData('is_repricing') &&
            !$row->getData('is_repricing_disabled') &&
            !$row->getData('is_repricing_inactive') &&
            !$row->getData('is_variation_parent')
        ) {
            $accountId = $this->listing['account_id'];
            $sku = $row->getData('amazon_sku');

            $regularPriceValue =<<<HTML
<a id="m2epro_repricing_price_value_{$sku}"
   class="m2epro-repricing-price-value"
   sku="{$sku}"
   account_id="{$accountId}"
   href="javascript:void(0)"
   onclick="AmazonListingProductRepricingPriceObj.showRepricingPrice()">{$regularPriceValue}</a>
HTML;
        }

        $resultHtml = '';

        $salePrice = $row->getData('online_regular_sale_price');
        if (!$row->getData('is_variation_parent') && (float)$salePrice > 0) {
            $currentTimestamp = strtotime($this->getHelper('Data')->getCurrentGmtDate(false, 'Y-m-d 00:00:00'));

            $startDateTimestamp = strtotime($row->getData('online_regular_sale_price_start_date'));
            $endDateTimestamp   = strtotime($row->getData('online_regular_sale_price_end_date'));

            if ($currentTimestamp <= $endDateTimestamp) {
                $fromDate = $this->_localeDate->formatDate(
                    $row->getData('online_regular_sale_price_start_date'),
                    \IntlDateFormatter::MEDIUM
                );

                $toDate = $this->_localeDate->formatDate(
                    $row->getData('online_regular_sale_price_end_date'),
                    \IntlDateFormatter::MEDIUM
                );

                $intervalHtml = <<<HTML
<span style="color: gray;">
    <strong>From:</strong> {$fromDate}<br/>
    <strong>To:</strong> {$toDate}
</span>
HTML;
                $intervalHtml = $this->getTooltipHtml($intervalHtml, '', ['m2epro-field-tooltip-price-info']);
                $salePriceValue = $this->convertAndFormatPriceCurrency($salePrice, $currency);

                if ($currentTimestamp >= $startDateTimestamp &&
                    $currentTimestamp <= $endDateTimestamp &&
                    $salePrice < (float)$onlineRegularPrice
                ) {
                    $resultHtml .= '<span style="color: grey; text-decoration: line-through;">'.
                                    $regularPriceValue.'</span>'.$repricingHtml;
                    $resultHtml .= '<br/>'.$intervalHtml.'&nbsp;'.$salePriceValue;
                } else {
                    $resultHtml .= $regularPriceValue . $repricingHtml;
                    $resultHtml .= '<br/>'.$intervalHtml.
                        '<span style="color:gray;">'.'&nbsp;'.$salePriceValue.'</span>';
                }
            }
        }

        if (empty($resultHtml)) {
            $resultHtml = $regularPriceValue . $repricingHtml;
        }

        $onlineBusinessPrice = $row->getData('online_business_price');
        if ((float)$onlineBusinessPrice > 0) {
            $businessPriceValue = '<strong>B2B:</strong> '.
                                  $this->convertAndFormatPriceCurrency($onlineBusinessPrice, $currency);

            $businessDiscounts = $row->getData('online_business_discounts');
            if (!empty($businessDiscounts) && $businessDiscounts = json_decode($businessDiscounts, true)) {
                $discountsHtml = '';

                foreach ($businessDiscounts as $qty => $price) {
                    $price = $this->convertAndFormatPriceCurrency($price, $currency);
                    $discountsHtml .= 'QTY >= '.(int)$qty.', price '.$price.'<br />';
                }

                $discountsHtml = $this->getTooltipHtml($discountsHtml, '', ['m2epro-field-tooltip-price-info']);
                $businessPriceValue = $discountsHtml .'&nbsp;'. $businessPriceValue;
            }

            if (!empty($resultHtml)) {
                $businessPriceValue = '<br />'.$businessPriceValue;
            }

            $resultHtml .= $businessPriceValue;
        }

        return $resultHtml;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer\Status $status */
        $status = $this->createBlock('Amazon_Grid_Column_Renderer_Status');
        $status->setParentAndChildReviseScheduledCache($this->parentAndChildReviseScheduledCache);

        return $status->render($row);
    }

    // ---------------------------------------

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute' => 'sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'amazon_sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'name', 'like' => '%' . $value . '%']
            ]
        );
    }

    protected function callbackFilterGeneralId($collection, $column)
    {
        $inputValue = $column->getFilter()->getValue('input');
        if ($inputValue !== null) {
            $collection->addFieldToFilter('general_id', ['like' => '%' . $inputValue . '%']);
        }

        $selectValue = $column->getFilter()->getValue('select');
        if ($selectValue !== null) {
            $collection->addFieldToFilter('is_general_id_owner', $selectValue);
        }
    }

    protected function callbackFilterQty($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = '';

        if (isset($value['from']) && $value['from'] != '') {
            $where .= 'online_qty >= ' . (int)$value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }
            $where .= 'online_qty <= ' . (int)$value['to'];
        }

        if (isset($value['afn']) && $value['afn'] !== '') {
            if (!empty($where)) {
                $where = '(' . $where . ') OR ';
            }

            if ((int)$value['afn'] == 1) {
                $where .= 'is_afn_channel = 1';
            } else {
                $partialFilter = \Ess\M2ePro\Model\Amazon\Listing\Product::VARIATION_PARENT_IS_AFN_STATE_PARTIAL;
                $where .= "(is_afn_channel = 0 OR variation_parent_afn_state = {$partialFilter})";
            }
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $condition = '';

        if (isset($value['from']) || isset($value['to'])) {
            if (isset($value['from']) && $value['from'] != '') {
                $condition = 'min_online_price >= \''.(float)$value['from'].'\'';
            }
            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'min_online_price <= \''.(float)$value['to'].'\'';
            }

            $condition = '(' . $condition . ') OR (';

            if (isset($value['from']) && $value['from'] != '') {
                $condition .= 'max_online_price >= \''.(float)$value['from'].'\'';
            }
            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'max_online_price <= \''.(float)$value['to'].'\'';
            }

            $condition .= ')';
        }

        if ($this->getHelper('Component_Amazon_Repricing')->isEnabled() &&
            (isset($value['is_repricing']) && $value['is_repricing'] !== '')) {
            if (!empty($condition)) {
                $condition = '(' . $condition . ') OR ';
            }

            if ((int)$value['is_repricing'] == 1) {
                $condition .= 'is_repricing = 1';
            } else {
                $partialFilter = \Ess\M2ePro\Model\Amazon\Listing\Product::VARIATION_PARENT_IS_REPRICING_STATE_PARTIAL;
                $condition .= "(is_repricing = 0 OR variation_parent_repricing_state = {$partialFilter})";
            }
        }

        $collection->getSelect()->having($condition);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where("lp.status = {$value} OR
            (alp.variation_child_statuses REGEXP '\"{$value}\":[^0]') AND alp.is_variation_parent = 1");
    }

    //########################################

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    public function getTooltipHtml($content, $id = '', $classes = [])
    {
        $classes = implode(' ', $classes);

        return <<<HTML
    <div id="{$id}" class="m2epro-field-tooltip admin__field-tooltip {$classes}">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content" style="">
            {$content}
        </div>
    </div>
HTML;
    }

    //########################################

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    ListingGridObj.afterInitPage();
JS
            );
        }

        return parent::_toHtml();
    }

    //########################################

    private function getLockedData($row)
    {
        $listingProductId = $row->getData('id');
        if (!isset($this->lockedDataCache[$listingProductId])) {
            $objectLocks = $this->activeRecordFactory->getObjectLoaded('Listing\Product', $listingProductId)
                ->getProcessingLocks();
            $tempArray = [
                'object_locks' => $objectLocks,
                'in_action' => !empty($objectLocks),
            ];
            $this->lockedDataCache[$listingProductId] = $tempArray;
        }

        return $this->lockedDataCache[$listingProductId];
    }

    //########################################

    private function getGeneralIdColumnValueEmptyGeneralId($row)
    {
        // ---------------------------------------
        if ((int)$row->getData('amazon_status') != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return '<i style="color:gray;">'.$this->__('receiving...').'</i>';
        }
        // ---------------------------------------

        // ---------------------------------------
        $iconPath = 'Ess_M2ePro::images/search_statuses/';
        // ---------------------------------------

        // ---------------------------------------
        $lpId = $row->getData('id');

        $productTitle = $row->getData('name');
        if (strlen($productTitle) > 60) {
            $productTitle = substr($productTitle, 0, 60) . '...';
        }
        $productTitle = $this->getHelper('Data')->escapeHtml($productTitle);
        $productTitle = $this->__('Assign ASIN/ISBN For &quot;%product_title%&quot;', $productTitle);
        $productTitle = $this->getHelper('Data')->escapeJs($productTitle);
        // ---------------------------------------

        // ---------------------------------------

        $searchSettingsStatus = $row->getData('search_settings_status');

        // ---------------------------------------
        if ($searchSettingsStatus == \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS) {
            $tip = $this->__('Automatic ASIN/ISBN Search in Progress.');
            $iconSrc = $this->getViewFileUrl($iconPath.'processing.gif');

            return <<<HTML
&nbsp;
<a href="javascript: void(0);" title="{$tip}">
    <img src="{$iconSrc}" alt="">
</a>
HTML;
        }
        // ---------------------------------------

        // ---------------------------------------
        $searchSettingsData = $row->getData('search_settings_data');

        $suggestData = [];
        if ($searchSettingsData !== null) {
            $searchSettingsData = $this->getHelper('Data')->jsonDecode($searchSettingsData);
            !empty($searchSettingsData['data']) && $suggestData = $searchSettingsData['data'];
        }
        // ---------------------------------------

        $na = $this->__('N/A');

        if (!empty($suggestData)) {
            $tip = $this->__('Choose ASIN/ISBN from the list');

            return <<<HTML
{$na} &nbsp;
<a href="javascript:;" title="{$tip}" class="amazon-listing-view-icon amazon-listing-view-generalId-search-data"
   onclick="ListingGridObj.productSearchHandler.openPopUp(1,'{$productTitle}',{$lpId})">
</a>
HTML;
        }

        if ($searchSettingsStatus == \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_NOT_FOUND) {
            $tip = $this->__(
                'There were no Products found on Amazon according to the Listing Search Settings.'
            );
            $tip = $this->getHelper('Data')->escapeJs($tip);

            return <<<HTML
{$na} &nbsp;
<a href="javascript: void(0);"
   title="{$tip}"
   class="amazon-listing-view-icon amazon-listing-view-generalId-search-error"
    onclick="ListingGridObj.productSearchHandler.openPopUp(0,'{$productTitle}',{$lpId});">
</a>
HTML;
        }

        $tip = $this->__('Search for ASIN/ISBN');

        return <<<HTML
{$na} &nbsp;
<a href="javascript:;" title="{$tip}" class="amazon-listing-view-icon amazon-listing-view-generalId-search"
   onclick="ListingGridObj.productSearchHandler.openPopUp(0,'{$productTitle}',{$lpId});">
</a>
HTML;
    }

    private function getGeneralIdColumnValueNotEmptyGeneralId($row)
    {
        $generalId = $row->getData('general_id');

        $url = $this->getHelper('Component\Amazon')->getItemUrl($generalId, $this->listing->getMarketplaceId());

        $generalIdOwnerHtml = '';
        if ($row->getData('is_general_id_owner') == \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES) {
            $generalIdOwnerHtml = '<br/><span style="font-size: 10px; color: grey;">'.
                                   $this->__('creator of ASIN/ISBN').
                                  '</span>';
        }

        if ((int)$row->getData('amazon_status') != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
            return <<<HTML
<a href="{$url}" target="_blank">{$generalId}</a>{$generalIdOwnerHtml}
HTML;
        }

        $generalIdSearchInfo = $row->getData('general_id_search_info');

        if (!empty($generalIdSearchInfo)) {
            $generalIdSearchInfo = $this->getHelper('Data')->jsonDecode($generalIdSearchInfo);
        }

        if (!empty($generalIdSearchInfo['is_set_automatic'])) {
            $tip = $this->__('ASIN/ISBN was found automatically');

            $text = <<<HTML
<a href="{$url}" target="_blank" title="{$tip}" style="color:#40AADB;">{$generalId}</a>
HTML;
        } else {
            $text = <<<HTML
<a href="{$url}" target="_blank">{$generalId}</a>
HTML;
        }

        // ---------------------------------------
        $hasInActionLock = $this->getLockedData($row);
        $hasInActionLock = $hasInActionLock['in_action'];
        // ---------------------------------------

        if ($hasInActionLock) {
            return $text . $generalIdOwnerHtml;
        }

        $listingProductId = (int)$row->getData('id');

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();
        $variationChildStatuses = $row->getData('variation_child_statuses');

        if ($variationManager->isVariationParent() && !empty($variationChildStatuses)) {
            $variationChildStatuses = $this->getHelper('Data')->jsonDecode($variationChildStatuses);
            unset($variationChildStatuses[\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED]);

            foreach ($variationChildStatuses as $variationChildStatus) {
                if (!empty($variationChildStatus)) {
                    return $text . $generalIdOwnerHtml;
                }
            }
        }

        $tip = $this->__('Unassign ASIN/ISBN');

        $text .= <<<HTML
&nbsp;
<a href="javascript:;"
    class="amazon-listing-view-icon amazon-listing-view-generalId-remove"
    onclick="ListingGridObj.productSearchHandler.showUnmapFromGeneralIdPrompt({$listingProductId});"
    title="{$tip}">
</a>{$generalIdOwnerHtml}
HTML;

        return $text;
    }

    private function getGeneralIdColumnValueGeneralIdOwner($row)
    {
        $text = $this->__('New ASIN/ISBN');

        // ---------------------------------------
        $hasInActionLock = $this->getLockedData($row);
        $hasInActionLock = $hasInActionLock['in_action'];
        // ---------------------------------------

        if ($hasInActionLock) {
            return $text;
        }

        $tip = $this->__('Unassign ASIN/ISBN');

        $lpId = $row->getData('id');

        $text .= <<<HTML
&nbsp;
<a href="javascript:;"
    class="amazon-listing-view-icon amazon-listing-view-generalId-remove"
    onclick="ListingGridObj.productSearchHandler.showUnmapFromGeneralIdPrompt({$lpId});"
    title="{$tip}">
</a>
HTML;
        return $text;
    }

    //########################################

    protected function getChildProductsWarningsData()
    {
        if ($this->childProductsWarningsData === null) {
            $this->childProductsWarningsData = [];

            $productsIds = [];
            foreach ($this->getCollection()->getItems() as $row) {
                $productsIds[] = $row['id'];
            }

            $connection = $this->resourceConnection->getConnection();
            $tableAmazonListingProduct = $this->activeRecordFactory
                ->getObject('Amazon_Listing_Product')->getResource()->getMainTable();

            $select = $connection->select();
            $select->distinct(true);
            $select->from(['alp' => $tableAmazonListingProduct], ['variation_parent_id'])
                ->where('variation_parent_id IN (?)', $productsIds)
                ->where(
                    'is_variation_product_matched = 0 OR
                    (general_id IS NOT NULL AND is_variation_channel_matched = 0)'
                );

            $this->childProductsWarningsData = $connection->fetchCol($select);
        }

        return $this->childProductsWarningsData;
    }

    protected function hasChildWithWarning($listingProductId)
    {
        return in_array($listingProductId, $this->getChildProductsWarningsData());
    }

    //########################################

    private function convertAndFormatPriceCurrency($price, $currency)
    {
        return $this->localeCurrency->getCurrency($currency)->toCurrency($price);
    }

    //########################################
}
