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
        $this->setId('walmartListingViewWalmartGrid'.$this->listing['id']);
        // ---------------------------------------

        $this->showAdvancedFilterProductsOption = false;
    }

    //########################################

    protected function _prepareCollection()
    {
        // Get collection
        // ---------------------------------------
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->setListingProductModeOn();
        $collection->setStoreId($this->listing->getStoreId());
        $collection->setListing($this->listing->getId());

        if ($this->isFilterOrSortByPriceIsUsed('online_price', 'walmart_online_price')) {
            $collection->setIsNeedToUseIndexerParent(true);
        }

        $collection
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->joinStockItem();

        // ---------------------------------------

        // Join listing product tables
        // ---------------------------------------
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            [
                'id'              => 'id',
                'walmart_status'   => 'status',
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
                'channel_url'                    => 'channel_url',
                'item_id'                        => 'item_id',
                'online_qty'                     => 'online_qty',
                'online_price'                   => 'online_price',
                'is_variation_parent'            => 'is_variation_parent',
                'is_details_data_changed'        => 'is_details_data_changed',
                'is_online_price_invalid'        => 'is_online_price_invalid',
                'online_start_date'              => 'online_start_date',
                'online_end_date'                => 'online_end_date',
                'status_change_reasons'          => 'status_change_reasons'
            ],
            '{{table}}.variation_parent_id is NULL'
        );

        if ($collection->isNeedUseIndexerParent()) {
            $collection->joinIndexerParent();
        } else {
            $collection->setIsNeedToInjectPrices(true);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header'    => $this->__('Product ID'),
            'align'     => 'right',
            'width'     => '100px',
            'type'      => 'number',
            'index'     => 'entity_id',
            'frame_callback' => [$this, 'callbackColumnProductId']
        ]);

        $this->addColumn('name', [
            'header'    => $this->__('Product Title / Product SKU'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'name',
            'filter_index' => 'name',
            'escape'       => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('sku', [
            'header' => $this->__('SKU'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'walmart_sku',
            'filter_index' => 'walmart_sku',
            'frame_callback' => [$this, 'callbackColumnWalmartSku']
        ]);

        $this->addColumn('gtin', [
            'header' => $this->__('GTIN'),
            'align' => 'left',
            'width' => '140px',
            'type' => 'text',
            'index' => 'gtin',
            'filter_index' => 'gtin',
            'frame_callback' => [$this, 'callbackColumnGtin'],
            'filter_condition_callback' => [$this, 'callbackFilterGtin']
        ]);

        $this->addColumn('online_qty', [
            'header' => $this->__('QTY'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'frame_callback' => [$this, 'callbackColumnAvailableQty'],
            'filter_condition_callback' => [$this, 'callbackFilterQty']
        ]);

        $dir = $this->getParam($this->getVarNameDir(), $this->_defaultDir);

        if ($dir == 'desc') {
            $priceSortField = 'max_online_price';
        } else {
            $priceSortField = 'min_online_price';
        }

        $this->addColumn('online_price', [
            'header' => $this->__('Price'),
            'align' => 'right',
            'width' => '110px',
            'type' => 'number',
            'index' => $priceSortField,
            'filter_index' => $priceSortField,
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice']
        ]);

        $statusColumn = [
            'header' => $this->__('Status'),
            'width' => '155px',
            'index' => 'walmart_status',
            'filter_index' => 'walmart_status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Inactive (Blocked)')
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
            'filter_condition_callback' => [$this, 'callbackFilterStatus']
        ];

        if ($this->getHelper('View\Walmart')->isResetFilterShouldBeShown($this->listing->getId())) {
            $statusColumn['filter'] = 'Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Filter\Status';
        }

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
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ], 'actions');

        $this->getMassactionBlock()->addItem('revise', [
            'label'    => $this->__('Revise Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ], 'actions');

        $this->getMassactionBlock()->addItem('relist', [
            'label'    => $this->__('Relist Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ], 'actions');

        $this->getMassactionBlock()->addItem('stop', [
            'label'    => $this->__('Stop Item(s)'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ], 'actions');

        $this->getMassactionBlock()->addItem('stopAndRemove', [
            'label'    => $this->__('Stop on Channel / Remove from Listing'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ], 'actions');

        $this->getMassactionBlock()->addItem('deleteAndRemove', [
            'label'    => $this->__('Retire on Channel / Remove from Listing'),
            'url'      => '',
            'confirm'  => $this->__('Are you sure?')
        ], 'actions');

        // ---------------------------------------

        if ($this->getHelper('View\Walmart')->isResetFilterShouldBeShown($this->listing->getId())) {
            $this->getMassactionBlock()->addItem('resetProducts', [
                'label'    => $this->__('Reset Inactive (Blocked) Item(s)'),
                'url'      => '',
                'confirm'  => $this->__('Are you sure?')
            ], 'other');
        }

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
            $vpmt = $this->__('Manage Variations of &quot;%s%&quot; ', $productTitle);
            $vpmt = addslashes($vpmt);

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
    onclick="ListingGridHandlerObj.variationProductManageHandler.openPopUp({$listingProductId}, '{$vpmt}')"
    title="{$linkTitle}">{$linkContent}</a>&nbsp;{$problemIcon}
</div>
HTML;

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

    public function callbackColumnWalmartSku($value, $row, $column, $isExport)
    {
        $isVariationParent = $row->getData('is_variation_parent');

        if ($value === null || $value === '') {
            $value = $this->__('N/A');
        }

        $productId = $row->getData('id');

        if ($row->getData('walmart_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED &&
            !$row->getData('is_variation_parent')) {
            $value = <<<HTML
<div class="walmart-sku">
    {$value}&nbsp;&nbsp;
    <a href="#" class="walmart-sku-edit"
       onclick="ListingGridHandlerObj.editChannelDataHandler.showEditSkuPopup({$productId})">(edit)</a>
</div>
HTML;
        }

        if (!$isVariationParent &&
            ($row->getData('is_details_data_changed') || $row->getData('is_online_price_invalid'))) {
            $msg = '';

            if ($row->getData('is_details_data_changed')) {
                $message = <<<HTML
Item Details, e.g. Product Tax Code, Lag Time, Shipping, Description, Image, Category, etc. settings, need to be
updated on Walmart.<br>
To submit new Item Details, apply the Revise action. Use the Advanced Filter to select all Items with the Details
changes and update them in bulk.
HTML;
                $msg .= '<p>'.$this->__($message).'</p>';
            }

            if ($row->getData('is_online_price_invalid')) {
                $message = <<<HTML
Item Price violates Walmart pricing rules. Please adjust the Item Price to comply with the Walmart requirements.<br>
Once the changes are applied, Walmart Item will become Active automatically.
HTML;
                $msg .= '<p>'.$this->__($message).'</p>';
            }

            if (empty($msg)) {
                return $value;
            }

            $value .= <<<HTML
<span class="fix-magento-tooltip">
    {$this->getTooltipHtml($msg, 'map_link_defected_message_icon_'.$row->getId())}
</span>
HTML;
        }

        return $value;
    }

    public function callbackColumnGtin($gtin, $row, $column, $isExport)
    {
        if (empty($gtin)) {
            return $this->__('N/A');
        }

        $productId = $row->getData('id');
        $gtinHtml = $this->getHelper('Data')->escapeHtml($gtin);
        $channelUrl = $row->getData('channel_url');

        if (!empty($channelUrl)) {
            $gtinHtml = <<<HTML
<a href="{$channelUrl}" target="_blank">{$gtin}</a>
HTML;
        }

        $html = '<div class="walmart-identifiers-gtin">'.$gtinHtml;

        if ($row->getData('walmart_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED &&
            !$row->getData('is_variation_parent')) {
            $html .= <<<HTML
&nbsp;&nbsp;<a href="#" class="walmart-identifiers-gtin-edit"
   onclick="ListingGridHandlerObj.editChannelDataHandler.showIdentifiersPopup('$productId')">(edit)</a>
HTML;
        }

        $html .= '</div>';

        $identifiers = [
            'UPC'        => $row->getData('upc'),
            'EAN'        => $row->getData('ean'),
            'ISBN'       => $row->getData('isbn'),
            'Walmart ID' => $row->getData('wpid'),
            'Item ID'    => $row->getData('item_id')
        ];

        $htmlAdditional = '';
        foreach ($identifiers as $title => $value) {
            if (empty($value)) {
                continue;
            }

            if (($row->getData('upc') || $row->getData('ean') || $row->getData('isbn')) &&
                ($row->getData('wpid') || $row->getData('item_id')) && $title == 'Walmart ID') {
                $htmlAdditional .= "<div class='separator-line'></div>";
            }
            $identifierCode  = $this->__($title);
            $identifierValue = $this->getHelper('Data')->escapeHtml($value);

            $htmlAdditional .= <<<HTML
<div>
    <span style="display: inline-block; float: left;">
        <strong>{$identifierCode}:</strong>&nbsp;&nbsp;&nbsp;&nbsp;
    </span>
    <span style="display: inline-block; float: right;">
        {$identifierValue}
    </span>
    <div style="clear: both;"></div>
</div>
HTML;
        }

        if ($htmlAdditional != '') {
            $html .= <<<HTML
<span class="fix-magento-tooltip">
    {$this->getTooltipHtml($htmlAdditional)}
</span>
HTML;
        }

        return $html;
    }

    public function callbackColumnAvailableQty($value, $row, $column, $isExport)
    {
        if (!$row->getData('is_variation_parent')) {
            if ($row->getData('walmart_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
            }

            if ($value === null || $value === '' ||
                ($row->getData('walmart_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
                    !$row->getData('is_online_price_invalid'))) {
                return $this->__('N/A');
            }

            if ($value <= 0) {
                return '<span style="color: red;">0</span>';
            }

            return $value;
        }

        $variationChildStatuses = $this->getHelper('Data')->jsonDecode($row->getData('variation_child_statuses'));

        if (empty($variationChildStatuses) || $value === null || $value === '') {
            return $this->__('N/A');
        }

        $activeChildrenCount = 0;
        foreach ($variationChildStatuses as $childStatus => $count) {
            if ($childStatus == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                continue;
            }
            $activeChildrenCount += (int)$count;
        }

        if ($activeChildrenCount == 0) {
            return $this->__('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ((!$row->getData('is_variation_parent') &&
            $row->getData('walmart_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED)) {
            return '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        }

        $onlineMinPrice = (float)$row->getData('min_online_price');
        $onlineMaxPrice = (float)$row->getData('max_online_price');

        if (empty($onlineMinPrice) ||
            ($row->getData('walmart_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
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
        $listingProductId  = (int)$row->getData('id');
        $isVariationParent = (bool)(int)$row->getData('is_variation_parent');
        $additionalData    = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));
        $isListAction      = !empty($additionalData['is_list_action']);

        $html = $this->getViewLogIconHtml($listingProductId, $isVariationParent);

        if (!empty($additionalData['synch_template_list_rules_note'])) {
            $synchNote = $this->getHelper('View')->getModifiedLogMessage(
                $additionalData['synch_template_list_rules_note']
            );

            if (empty($html)) {
                $html = <<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning" style="float:right;">
    {$this->getTooltipHtml($synchNote, 'map_link_error_icon_'.$row->getId())}
</span>
HTML;
            } else {
                $html .= <<<HTML
<div id="synch_template_list_rules_note_{$listingProductId}" style="display: none">{$synchNote}</div>
HTML;
            }
        }

        $resetHtml = '';
        if ($row->getData('walmart_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED &&
            !$row->getData('is_online_price_invalid')) {
            $resetHtml = <<<HTML
<br/>
<span style="color: gray">[Can be fixed]</span>
HTML;
        }

        if (!$isVariationParent) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            $listingProduct = $this->walmartFactory
                ->getObjectLoaded('Listing\Product', $listingProductId);

            $statusChangeReasons = $listingProduct->getChildObject()->getStatusChangeReasons();

            return $html
                . $this->getProductStatus($row->getData('walmart_status'), $statusChangeReasons, $isListAction)
                . $resetHtml
                . $this->getLockedTag($row);
        } else {
            $statusNotListed = \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;
            $statusListed    = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
            $statusStopped   = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
            $statusBlocked   = \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED;

            $variationChildStatuses = $row->getData('variation_child_statuses');
            if (empty($variationChildStatuses)) {
                return $html
                    . $this->getProductStatus($statusNotListed, [], $isListAction)
                    . $this->getLockedTag($row);
            }

            $variationChildStatuses = $this->getHelper('Data')->jsonDecode($variationChildStatuses);

            $sortedStatuses = [];

            if (isset($variationChildStatuses[$statusNotListed])) {
                $sortedStatuses[$statusNotListed] = $variationChildStatuses[$statusNotListed];
            }
            if (isset($variationChildStatuses[$statusListed])) {
                $sortedStatuses[$statusListed] = $variationChildStatuses[$statusListed];
            }
            if (isset($variationChildStatuses[$statusStopped])) {
                $sortedStatuses[$statusStopped] = $variationChildStatuses[$statusStopped];
            }
            if (isset($variationChildStatuses[$statusBlocked])) {
                $sortedStatuses[$statusBlocked] = $variationChildStatuses[$statusBlocked];
            }

            $linkTitle = $this->__('Show all Child Products with such Status');

            foreach ($sortedStatuses as $status => $productsCount) {
                if (empty($productsCount)) {
                    continue;
                }

                $filter = base64_encode('status=' . $status);

                $productTitle = $this->getHelper('Data')->escapeHtml($row->getData('name'));
                $vpmt = $this->__('Manage Variations of &quot;%s%&quot; ', $productTitle);
                $vpmt = addslashes($vpmt);

                $productsCount = <<<HTML
<a onclick="ListingGridHandlerObj.variationProductManageHandler.openPopUp({$listingProductId}, '{$vpmt}', '{$filter}')"
   class="hover-underline"
   title="{$linkTitle}"
   href="javascript:void(0)">[{$productsCount}]</a>
HTML;

                $html .= $this->getProductStatus($status, [], $isListAction) . '&nbsp;'. $productsCount . '<br/>';
            }

            $html .= $this->getLockedTag($row);
        }

        return $html;
    }

    private function getProductStatus($status, $statusChangeReasons = [], $isListAction = false)
    {
        $html = '';

        if ($isListAction) {
            $html = '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
        } else {
            switch ($status) {
                case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                    $html = '<span style="color: gray;">' . $this->__('Not Listed') . '</span>';
                    break;
                case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                    $html = '<span style="color: green;">' . $this->__('Active') . '</span>';
                    break;
                case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                    $html ='<span style="color: red;">' . $this->__('Inactive') . '</span>';
                    break;
                case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                    $html ='<span style="color: orange; font-weight: bold;">' .
                           $this->__('Inactive (Blocked)') . '</span>';
                    break;
            }
        }

        return $html .
            $this->getStatusChangeReasons($statusChangeReasons);
    }

    private function getStatusChangeReasons($statusChangeReasons)
    {
        if (empty($statusChangeReasons)) {
            return '';
        }

        $html = '<li style="margin-bottom: 5px;">'
            . implode('</li><li style="margin-bottom: 5px;">', $statusChangeReasons)
            . '</li>';

        return <<<HTML
        <span class="fix-magento-tooltip">
            {$this->getTooltipHtml($html)}
        </span>
HTML;
    }

    private function getLockedTag($row)
    {
        $html           = '';
        $childCount     = 0;
        $additionalData = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));

        if (!empty($additionalData['is_list_action'])) {
            $html .= '<br/><span style="color: #605fff">[List in Progress...]</span>';
        } else {
            $tempLocks = $this->getLockedData($row);
            $tempLocks = $tempLocks['object_locks'];

            foreach ($tempLocks as $lock) {
                switch ($lock->getTag()) {
                    case 'list_action':
                        $html .= '<br/><span style="color: #605fff">[List in Progress...]</span>';
                        break;

                    case 'relist_action':
                        $html .= '<br/><span style="color: #605fff">[Relist in Progress...]</span>';
                        break;

                    case 'revise_action':
                        $html .= '<br/><span style="color: #605fff">[Revise in Progress...]</span>';
                        break;

                    case 'stop_action':
                        $html .= '<br/><span style="color: #605fff">[Stop in Progress...]</span>';
                        break;

                    case 'stop_and_remove_action':
                        $html .= '<br/><span style="color: #605fff">[Stop And Remove in Progress...]</span>';
                        break;

                    case 'delete_and_remove_action':
                        $html .= '<br/><span style="color: #605fff">[Remove in Progress...]</span>';
                        break;

                    case 'child_products_in_action':
                        $childCount++;
                        break;

                    default:
                        break;
                }
            }
        }

        if ($childCount > 0) {
            $html .= '<br/><span style="color: #605fff">[Child(s) in Action...]</span>';
        }

        return $html;
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
            $collection->addFieldToFilter($index, \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED)
                       ->addFieldToFilter('is_online_price_invalid', 0);
        }
    }

    // ---------------------------------------

    public function getViewLogIconHtml($listingProductId, $isVariationParent)
    {
        $listingProductId = (int)$listingProductId;
        $availableActionsId = array_keys($this->getAvailableActions());

        $connection = $this->resourceConnection->getConnection();

        // Get last messages
        // ---------------------------------------
        $dbSelect = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getMainTable(),
                ['action_id','action','type','description','create_date','initiator','listing_product_id']
            )
            ->where('`action` IN (?)', $availableActionsId)
            ->order(['id DESC'])
            ->limit(\Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::PRODUCTS_LIMIT);

        if ($isVariationParent) {
            $dbSelect->where('`listing_product_id` = ? OR `parent_listing_product_id` = ?', $listingProductId);
        } else {
            $dbSelect->where('`listing_product_id` = ?', $listingProductId);
        }

        $logs = $connection->fetchAll($dbSelect);

        if (empty($logs)) {
            return '';
        }

        // ---------------------------------------

        $summary = $this->createBlock('Walmart_Listing_Log_Grid_LastActions')->setData([
            'entity_id' => $listingProductId,
            'logs'      => $logs,
            'available_actions' => $this->getAvailableActions(),
            'is_variation_parent' => $isVariationParent,
            'view_help_handler' => 'ListingGridHandlerObj.viewItemHelp',
            'hide_help_handler' => 'ListingGridHandlerObj.hideItemHelp',
        ]);

        return $summary->toHtml();
    }

    private function getAvailableActions()
    {
        return [
            Log::ACTION_LIST_PRODUCT_ON_COMPONENT       => $this->__('List'),
            Log::ACTION_RELIST_PRODUCT_ON_COMPONENT     => $this->__('Relist'),
            Log::ACTION_REVISE_PRODUCT_ON_COMPONENT     => $this->__('Revise'),
            Log::ACTION_STOP_PRODUCT_ON_COMPONENT       => $this->__('Stop'),
            Log::ACTION_DELETE_PRODUCT_FROM_COMPONENT   => $this->__('Remove from Channel'),
            Log::ACTION_STOP_AND_REMOVE_PRODUCT         => $this->__('Stop on Channel / Remove from Listing'),
            Log::ACTION_DELETE_AND_REMOVE_PRODUCT       => $this->__('Remove from Channel & Listing'),
            Log::ACTION_DELETE_PRODUCT_FROM_LISTING     => $this->__('Remove from Listing'),
            Log::ACTION_RESET_BLOCKED_PRODUCT           => $this->__('Reset Inactive (Blocked) Item'),
            Log::ACTION_CHANNEL_CHANGE                  => $this->__('Channel Change'),
            Log::ACTION_SWITCH_TO_AFN_ON_COMPONENT      => $this->__('Switch to AFN'),
            Log::ACTION_SWITCH_TO_MFN_ON_COMPONENT      => $this->__('Switch to MFN'),
        ];
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
    ListingGridHandlerObj.afterInitPage();
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
