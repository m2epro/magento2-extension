<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Walmart;

use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;
use Ess\M2ePro\Model\Listing\Log;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Walmart\Grid
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
    protected $walmartFactory;
    protected $localeCurrency;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->walmartFactory = $walmartFactory;
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
        $this->setId('walmartListingViewGrid'.$this->listing['id']);
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
                'status'          => 'status',
                'component_mode'  => 'component_mode',
                'additional_data' => 'additional_data'
            ],
            [
                'listing_id' => (int)$this->listing['id']
            ]
        );

        $wlpTable = $this->activeRecordFactory->getObject('Walmart_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['wlp' => $wlpTable],
            'listing_product_id=id',
            [
                'variation_child_statuses'       => 'variation_child_statuses',
                'walmart_sku'                    => 'sku',
                'gtin'                           => 'gtin',
                'upc'                            => 'upc',
                'ean'                            => 'ean',
                'isbn'                           => 'isbn',
                'wpid'                           => 'wpid',
                'item_id'                        => 'item_id',
                'online_qty'                     => 'online_qty',
                'online_price'                   => 'online_price',
                'is_variation_parent'            => 'is_variation_parent',
                'is_online_price_invalid'        => 'is_online_price_invalid',
                'online_start_date'              => 'online_start_date',
                'online_end_date'                => 'online_end_date',
                'status_change_reasons'          => 'status_change_reasons'
            ],
            '{{table}}.variation_parent_id is NULL'
        );

        if ($this->isFilterOrSortByPriceIsUsed('online_price', 'walmart_online_price')) {
            $collection->joinIndexerParent();
        } else {
            $collection->setIsNeedToInjectPrices(true);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        $collection = $this->walmartFactory->getObject('Listing_Product')->getCollection();
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
            'header'         => $this->__('Product Title / Product SKU'),
            'align'          => 'left',
            'type'           => 'text',
            'index'          => 'name',
            'filter_index'   => 'name',
            'escape'         => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('sku', [
            'header'       => $this->__('SKU'),
            'align'        => 'left',
            'width'        => '150px',
            'type'         => 'text',
            'index'        => 'walmart_sku',
            'filter_index' => 'walmart_sku',
            'renderer' => '\Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Sku'
        ]);

        $this->addColumn('gtin', [
            'header'         => $this->__('GTIN'),
            'align'          => 'left',
            'width'          => '140px',
            'type'           => 'text',
            'index'          => 'gtin',
            'filter_index'   => 'gtin',
            'marketplace_id' => $this->listing->getMarketplaceId(),
            'renderer'       => '\Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Gtin',
            'filter_condition_callback' => [$this, 'callbackFilterGtin']
        ]);

        $this->addColumn('online_qty', [
            'header'       => $this->__('QTY'),
            'align'        => 'right',
            'width'        => '70px',
            'type'         => 'number',
            'index'        => 'online_qty',
            'filter_index' => 'online_qty',
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Qty',
            'filter_condition_callback' => [$this, 'callbackFilterQty']
        ]);

        $dir = $this->getParam($this->getVarNameDir(), $this->_defaultDir);

        if ($dir == 'desc') {
            $priceSortField = 'max_online_price';
        } else {
            $priceSortField = 'min_online_price';
        }

        $this->addColumn('online_price', [
            'header'         => $this->__('Price'),
            'align'          => 'right',
            'width'          => '110px',
            'type'           => 'number',
            'index'          => $priceSortField,
            'filter_index'   => $priceSortField,
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice']
        ]);

        $statusColumn = [
            'header' => $this->__('Status'),
            'width'  => '155px',
            'index'  => 'status',
            'filter_index' => 'status',
            'type'     => 'options',
            'sortable' => false,
            'options'  => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Inactive (Blocked)')
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
            'filter_condition_callback' => [$this, 'callbackFilterStatus']
        ];

        $isShouldBeShown = $this->getHelper('View_Walmart')->isResetFilterShouldBeShown(
            'listing_id',
            $this->listing->getId()
        );

        $isShouldBeShown && $statusColumn['filter'] = 'Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Filter\Status';

        $this->addColumn('status', $statusColumn);

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
            'actions' => $this->__('Actions'),
            'other'   => $this->__('Other'),
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
            'label'    => $this->__('Retire on Channel / Remove from Listing'),
            'url'      => ''
        ], 'actions');

        // ---------------------------------------

        $this->getMassactionBlock()->addItem('resetProducts', [
            'label'    => $this->__('Reset Inactive (Blocked) Item(s)'),
            'url'      => ''
        ], 'other');

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        $productTitle = $this->getHelper('Data')->escapeHtml($productTitle);

        $value = '<span>'.$productTitle.'</span>';

        $sku = $row->getData('sku');

        if ($row->getData('sku') === null) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                ->setProductId($row->getData('entity_id'))
                ->getSku();
        }

        $value .= '<br/><strong>'.$this->__('SKU') .
            ':</strong> '.$this->getHelper('Data')->escapeHtml($sku) . '<br/>';

        $listingProductId = (int)$row->getData('id');
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $listingProductId);

        if (!$listingProduct->getChildObject()->getVariationManager()->isVariationProduct()) {
            return $value;
        }

        $gtin = $row->getData('gtin');

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation $parentType */
        $parentType = $variationManager->getTypeModel();

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

            if (!$parentType->hasChannelGroupId() &&
                !$listingProduct->isSetProcessingLock('child_products_in_action')) {
                $popupTitle = $this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml(
                    $this->__('Manage Magento Product Variations')
                ));

                $linkTitle = $this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml(
                    $this->__('Change "Magento Variations" Mode')
                ));

                $switchToIndividualJsMethod = <<<JS
WalmartListingProductVariationObj
    .setListingProductId({$listingProductId})
    .showSwitchToIndividualModePopUp('{$popupTitle}');
JS;

                if ($this->hideSwitchToIndividualConfirm) {
                    $switchToIndividualJsMethod = <<<JS
WalmartListingProductVariationObj
    .setListingProductId({$listingProductId})
        .showManagePopup('{$popupTitle}');
JS;
                }

                $value .= <<<HTML
&nbsp;
<a  href="javascript:"
    class="walmart-listing-view-switch-variation-mode"
    onclick="{$switchToIndividualJsMethod}"
    title="{$linkTitle}">
</a>
HTML;
            }

            $value .= '</div>';

            $linkContent = $this->__('Manage Variations');
            $vpmt = $this->getHelper('Data')->escapeJs(
                $this->__('Manage Variations of "'. $productTitle . '" ')
            );
            if (!empty($gtin)) {
                $vpmt .= '('. $gtin .')';
            }

            $problemStyle = '';
            $problemIcon = '';

            $linkTitle = $this->__('Open Manage Variations Tool');

            if (!$parentType->hasMatchedAttributes() || !$parentType->hasChannelAttributes()) {
                $linkTitle = $this->__('Action Required');
                $problemStyle = 'style="font-weight: bold; color: #FF0000;" ';
                $iconPath = $this->getViewFileUrl('Ess_M2ePro::images/error.png');
                $problemIcon = '<img style="vertical-align: middle;" src="'
                    . $iconPath . '" title="' . $linkTitle . '" alt="" width="16" height="16">';
            } elseif ($this->hasChildWithWarning($listingProductId)) {
                $linkTitle = $this->__('Action Required');
                $problemStyle = 'style="font-weight: bold;" ';
                $iconPath = $this->getViewFileUrl('Ess_M2ePro::images/warning.png');
                $problemIcon = '<img style="vertical-align: middle;" src="'
                    . $iconPath . '" title="' . $linkTitle . '" alt="" width="16" height="16">';
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
        class="walmart-listing-view-edit-variation"
        onclick="WalmartListingProductVariationObj
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
        class="walmart-listing-view-add-variation"
        onclick="WalmartListingProductVariationObj
            .setListingProductId({$listingProductId})
            .showManagePopup('{$popupTitle}');"
        title="{$linkTitle}"></a>
</div>
HTML;

        if (empty($gtin)) {
            $linkTitle = $this->getHelper('Data')->escapeJs($this->getHelper('Data')->escapeHtml(
                $this->__('Change "Magento Variations" Mode')
            ));

            $switchToParentJsMethod = <<<JS
WalmartListingProductVariationObj
    .setListingProductId({$listingProductId})
        .showSwitchToParentModePopUp('{$popupTitle}');
JS;

            if ($this->hideSwitchToParentConfirm) {
                $switchToParentJsMethod = <<<JS
WalmartListingProductVariationObj
    .setListingProductId({$listingProductId})
        .resetListingProductVariation();
JS;
            }

            $value .= <<<HTML
<div style="margin: 0 0 0 7px; float: left;">
    <a href="javascript:"
        class="walmart-listing-view-switch-variation-mode"
        onclick="{$switchToParentJsMethod}"
        title="{$linkTitle}"></a>
</div>
HTML;
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ((!$row->getData('is_variation_parent') &&
            $row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $onlineMinPrice = (float)$row->getData('min_online_price');
        $onlineMaxPrice = (float)$row->getData('max_online_price');

        if (empty($onlineMinPrice) ||
            ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
                !$row->getData('is_online_price_invalid'))) {
            return $this->__('N/A');
        }

        $currency = $this->listing->getMarketplace()->getChildObject()->getDefaultCurrency();

        if ($row->getData('is_variation_parent')) {
            $onlinePriceStr = '<span style="color: #f00;">0</span>';
            if (!empty($onlineMinPrice) && !empty($onlineMaxPrice)) {
                $onlineMinPriceStr = $this->convertAndFormatPriceCurrency($onlineMinPrice, $currency);
                $onlineMaxPriceStr = $this->convertAndFormatPriceCurrency($onlineMaxPrice, $currency);

                $onlinePriceStr = $onlineMinPriceStr
                    .(($onlineMinPrice != $onlineMaxPrice)?' - '
                        .$onlineMaxPriceStr:'');
            }

            return $onlinePriceStr;
        }

        $onlinePrice = $row->getData('online_price');
        if ((float)$onlinePrice <= 0) {
            $priceValue = '<span style="color: #f00;">0</span>';
        } else {
            $priceValue = $this->convertAndFormatPriceCurrency($onlinePrice, $currency);
        }

        $resultHtml = '';

        if (empty($resultHtml)) {
            $resultHtml = $priceValue;
        }

        return $resultHtml;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Status $status */
        $status = $this->createBlock('Walmart_Grid_Column_Renderer_Status');
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
                ['attribute'=>'sku','like'=>'%'.$value.'%'],
                ['attribute'=>'name', 'like'=>'%'.$value.'%']
            ]
        );
    }

    protected function callbackFilterGtin($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = <<<SQL
wlp.gtin LIKE '%{$value}%' OR
wlp.upc LIKE '%{$value}%' OR
wlp.ean LIKE '%{$value}%' OR
wlp.isbn LIKE '%{$value}%' OR
wlp.wpid LIKE '%{$value}%' OR
wlp.item_id LIKE '%{$value}%'
SQL;

        $collection->getSelect()->where($where);
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

        $collection->getSelect()->having($condition);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $index = $column->getIndex();

        if ($value == null || $index == null) {
            return;
        }

        if (is_array($value) && isset($value['value']) || is_string($value)) {
            if (is_string($value)) {
                $status = (int)$value;
            } else {
                $status = (int)$value['value'];
            }

            $collection->getSelect()->where(
                "lp.status = {$status} OR
                (wlp.variation_child_statuses REGEXP '\"{$status}\":[^0]' AND wlp.is_variation_parent = 1)"
            );
        }

        if (is_array($value) && isset($value['is_reset'])) {
            /** @var \Ess\M2ePro\Model\Listing\Product $childProducts */
            $collectionVariationParent = $this->walmartFactory->getObject('Listing\Product')->getCollection();
            $collectionVariationParent->addFieldToFilter('status', \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED);
            $collectionVariationParent->addFieldToFilter('variation_parent_id', ['notnull' => true]);
            $collectionVariationParent->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $collectionVariationParent->getSelect()->columns(['second_table.variation_parent_id']);

            $variationParentIds = $collectionVariationParent->getColumnValues('variation_parent_id');

            $collection->addFieldToFilter(
                [
                    ['attribute' => $index, 'eq' => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED],
                    ['attribute' => 'id', 'in' => $variationParentIds]
                ]
            )->addFieldToFilter('is_online_price_invalid', 0);
        }
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

    protected function getChildProductsWarningsData()
    {
        if ($this->childProductsWarningsData === null) {
            $this->childProductsWarningsData = [];

            $productsIds = [];
            foreach ($this->getCollection()->getItems() as $row) {
                $productsIds[] = $row['id'];
            }

            $connection = $this->resourceConnection->getConnection();
            $tableWalmartListingProduct = $this->activeRecordFactory
                ->getObject('Walmart_Listing_Product')->getResource()->getMainTable();

            $select = $connection->select();
            $select->distinct(true);
            $select->from(['wlp' => $tableWalmartListingProduct], ['variation_parent_id'])
                ->where('variation_parent_id IN (?)', $productsIds)
                ->where(
                    'is_variation_product_matched = 0'
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
