<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\View\Walmart;

use Ess\M2ePro\Model\Listing\Product;
use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;
use Ess\M2ePro\Model\Listing\Log;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Grid
{
    /** @var array */
    private $lockedDataCache = [];
    private $childProductsWarningsData;
    /** @var array */
    private $parentAndChildReviseScheduledCache = [];
    private $hideSwitchToIndividualConfirm;
    private $hideSwitchToParentConfirm;

    /** @var  \Ess\M2ePro\Model\Listing */
    protected $listing;
    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory */
    protected $magentoProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    protected $walmartFactory;
    /** @var \Magento\Framework\Locale\CurrencyInterface */
    protected $localeCurrency;
    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;
    /** @var \Ess\M2ePro\Helper\View\Walmart */
    protected $walmartViewHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Listing */
    private $walmartListingResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat */
    private $walmartSellingFormatResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Walmart\Listing $walmartListingResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat $walmartSellingFormatResource,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->walmartFactory = $walmartFactory;
        $this->localeCurrency = $localeCurrency;
        $this->resourceConnection = $resourceConnection;
        $this->walmartViewHelper = $walmartViewHelper;
        $this->walmartListingResource = $walmartListingResource;
        $this->walmartSellingFormatResource = $walmartSellingFormatResource;
        parent::__construct($context, $backendHelper, $dataHelper, $globalDataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setDefaultSort(false);

        $this->listing = $this->globalDataHelper->getValue('view_listing');

        $this->hideSwitchToIndividualConfirm =
            $this->listing->getSetting('additional_data', 'hide_switch_to_individual_confirm', 0);

        $this->hideSwitchToParentConfirm =
            $this->listing->getSetting('additional_data', 'hide_switch_to_parent_confirm', 0);

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingViewGrid' . $this->listing['id']);
        // ---------------------------------------

        $this->showAdvancedFilterProductsOption = false;
    }

    protected function _prepareCollection()
    {
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
                'id' => 'id',
                'status' => 'status',
                'component_mode' => 'component_mode',
                'additional_data' => 'additional_data',
                'listing_id' => 'listing_id',
            ],
            [
                'listing_id' => (int)$this->listing['id'],
            ]
        );

        $wlpTable = $this->activeRecordFactory->getObject('Walmart_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['wlp' => $wlpTable],
            'listing_product_id=id',
            [
                'variation_child_statuses' => 'variation_child_statuses',
                'walmart_sku' => 'sku',
                'gtin' => 'gtin',
                'upc' => 'upc',
                'ean' => 'ean',
                'isbn' => 'isbn',
                'wpid' => 'wpid',
                'item_id' => 'item_id',
                'online_qty' => 'online_qty',
                'online_price' => 'online_price',
                'is_variation_parent' => 'is_variation_parent',
                'is_online_price_invalid' => 'is_online_price_invalid',
                'online_start_date' => 'online_start_date',
                'online_end_date' => 'online_end_date',
                'status_change_reasons' => 'status_change_reasons',
            ],
            '{{table}}.variation_parent_id is NULL'
        );
        $collection->joinTable(
            ['wl' => $this->walmartListingResource->getMainTable()],
            'listing_id=listing_id',
            [
                'template_selling_format_id' => 'template_selling_format_id',
            ]
        );
        $collection->joinTable(
            ['wtsf' => $this->walmartSellingFormatResource->getMainTable()],
            'template_selling_format_id = template_selling_format_id',
            [
                'is_set_online_promotions'
                => new \Zend_Db_Expr('wtsf.promotions_mode = 1 AND wlp.online_promotions IS NOT NULL'),
            ]
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
            [
                'lps' => $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')
                                                   ->getResource()->getMainTable(),
            ],
            'lps.listing_product_id=main_table.id',
            []
        );

        $collection->addFieldToFilter('is_variation_parent', 0);
        $collection->addFieldToFilter('variation_parent_id', ['in' => $this->getCollection()->getColumnValues('id')]);
        $collection->addFieldToFilter('lps.action_type', \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'variation_parent_id' => 'second_table.variation_parent_id',
                'count' => new \Zend_Db_Expr('COUNT(lps.id)'),
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
        $this->addExportType('*/*/exportCsvListingGrid', __('CSV'));

        $this->addColumn('product_id', [
            'header' => __('Product ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'store_id' => $this->listing->getStoreId(),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => __('Product Title / Product SKU'),
            'header_export' => __('Product SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        $this->addColumn('sku', [
            'header' => __('Channel SKU'),
            'header_export' => __('Channel SKU'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'walmart_sku',
            'filter_index' => 'walmart_sku',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Sku::class,
        ]);

        $this->addColumn('gtin', [
            'header' => __('GTIN'),
            'align' => 'left',
            'width' => '140px',
            'type' => 'text',
            'index' => 'gtin',
            'filter_index' => 'gtin',
            'marketplace_id' => $this->listing->getMarketplaceId(),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Gtin::class,
            'filter_condition_callback' => [$this, 'callbackFilterGtin'],
        ]);

        $this->addColumn('online_qty', [
            'header' => __('QTY'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Qty::class,
            'filter_condition_callback' => [$this, 'callbackFilterQty'],
        ]);

        $dir = $this->getParam($this->getVarNameDir(), $this->_defaultDir);

        if ($dir == 'desc') {
            $priceSortField = 'max_online_price';
        } else {
            $priceSortField = 'min_online_price';
        }

        $this->addColumn('online_price', [
            'header' => __('Price'),
            'align' => 'right',
            'width' => '110px',
            'type' => 'number',
            'index' => $priceSortField,
            'filter_index' => $priceSortField,
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice'],
        ]);

        $statusColumn = [
            'header' => __('Status'),
            'width' => '155px',
            'index' => 'status',
            'filter_index' => 'status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => __('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => __('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE => __('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => __('Incomplete'),
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
            'filter_condition_callback' => [$this, 'callbackFilterStatus'],
        ];

        $isShouldBeShown = $this->walmartViewHelper->isResetFilterShouldBeShown(
            'listing_id',
            $this->listing->getId()
        );

        if ($isShouldBeShown) {
            $statusColumn['filter'] = \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Filter\Status::class;
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
            'actions' => __('Actions'),
            'other' => __('Other'),
        ];

        $this->getMassactionBlock()->setGroups($groups);

        $this->getMassactionBlock()->addItem('list', [
            'label' => __('List Item(s)'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('revise', [
            'label' => __('Revise Item(s)'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('relist', [
            'label' => __('Relist Item(s)'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('stop', [
            'label' => __('Stop Item(s)'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('stopAndRemove', [
            'label' => __('Stop on Channel / Remove from Listing'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('deleteAndRemove', [
            'label' => __('Retire on Channel / Remove from Listing'),
            'url' => '',
        ], 'actions');

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        $productTitle = $this->dataHelper->escapeHtml($productTitle);

        $value = '<span>' . $productTitle . '</span>';

        $sku = $row->getData('sku');

        if ($row->getData('sku') === null) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                                      ->setProductId($row->getData('entity_id'))
                                      ->getSku();
        }

        if ($isExport) {
            return $sku;
        }

        $value .= '<br/><strong>' . __('SKU') .
            ':</strong><span class="white-space-pre-wrap"> ' . $this->dataHelper->escapeHtml($sku) . '</span><br/>';

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

            if (
                !$parentType->hasChannelGroupId() &&
                !$listingProduct->isSetProcessingLock('child_products_in_action')
            ) {
                $popupTitle = $this->dataHelper->escapeJs(
                    $this->dataHelper->escapeHtml(
                        __('Manage Magento Product Variations')
                    )
                );

                $linkTitle = $this->dataHelper->escapeJs(
                    $this->dataHelper->escapeHtml(
                        __('Change "Magento Variations" Mode')
                    )
                );

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

            $linkContent = __('Manage Variations');
            $vpmt = $this->dataHelper->escapeJs(
                __('Manage Variations of "' . $productTitle . '" ')
            );
            if (!empty($gtin)) {
                $vpmt .= '(' . $gtin . ')';
            }

            $problemStyle = '';
            $problemIcon = '';

            $linkTitle = __('Open Manage Variations Tool');

            if (!$parentType->hasMatchedAttributes() || !$parentType->hasChannelAttributes()) {
                $linkTitle = __('Action Required');
                $problemStyle = 'style="font-weight: bold; color: #FF0000;" ';
                $iconPath = $this->getViewFileUrl('Ess_M2ePro::images/error.png');
                $problemIcon = '<img style="vertical-align: middle;" src="'
                    . $iconPath . '" title="' . $linkTitle . '" alt="" width="16" height="16">';
            } elseif ($this->hasChildWithWarning($listingProductId)) {
                $linkTitle = __('Action Required');
                $problemStyle = 'style="font-weight: bold;" ';
                $iconPath = $this->getViewFileUrl('Ess_M2ePro::images/warning.png');
                $problemIcon = '<img style="vertical-align: middle;" src="'
                    . $iconPath . '" title="' . $linkTitle . '" alt="" width="16" height="16">';
            }

            $value .= <<<HTML
<div style="float: left; margin: 0 0 0 7px">
    <a {$problemStyle}href="javascript:"
    onclick="ListingGridObj.variationProductManageHandler.openPopUp(
            {$listingProductId}, '{$this->dataHelper->escapeHtml($vpmt)}'
        )"
    title="{$linkTitle}">{$linkContent}</a>&nbsp;{$problemIcon}
</div>
HTML;

            if ($childListingProductIds = $this->getRequest()->getParam('child_listing_product_ids')) {
                $this->js->add(
                    <<<JS
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
                if ($option === '' || $option === null) {
                    $option = '--';
                }
                $value .= '<strong>' . $this->dataHelper->escapeHtml($attribute) .
                    '</strong>:&nbsp;' . $this->dataHelper->escapeHtml($option) . '<br/>';
            }
            $value .= '</div>';
        }

        // ---------------------------------------
        $hasInActionLock = $this->getLockedData($row);
        $hasInActionLock = $hasInActionLock['in_action'];
        // ---------------------------------------

        if (!$hasInActionLock) {
            $popupTitle = __('Manage Magento Product Variation');
            $linkTitle = __('Edit Variation');

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

        $popupTitle = __('Manage Magento Product Variations');
        $linkTitle = __('Add Another Variation(s)');

        $value .= <<<HTML
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
            $linkTitle = $this->dataHelper->escapeJs(
                $this->dataHelper->escapeHtml(
                    __('Change "Magento Variations" Mode')
                )
            );

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

    /**
     * @param $value
     * @param \Magento\Catalog\Model\Product $row
     * @param $column
     * @param $isExport
     *
     * @return mixed|string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if (!$row->getData('is_variation_parent') && $row->getData('status') == Product::STATUS_NOT_LISTED) {
            if ($isExport) {
                return '';
            }

            return '<span style="color: gray;">' . __('Not Listed') . '</span>';
        }

        $onlineMinPrice = (float)$row->getData('min_online_price');
        $onlineMaxPrice = (float)$row->getData('max_online_price');

        if (empty($onlineMinPrice)) {
            if ($isExport) {
                return '';
            }

            if (
                $row->getData('status') == Product::STATUS_NOT_LISTED ||
                $row->getData('is_variation_parent') ||
                $row->getData('status') == Product::STATUS_BLOCKED
            ) {
                return __('N/A');
            }

            return '<i style="color:gray;">receiving...</i>';
        }

        $currency = $this->listing->getMarketplace()->getChildObject()->getDefaultCurrency();

        $priceValue = $this->convertAndFormatPriceCurrency($value, $currency);

        if ($row->getData('is_online_price_invalid')) {
            if ($isExport) {
                return $priceValue;
            }

            $message = <<<HTML
Item Price violates Walmart pricing rules. Please adjust the Item Price to comply with the Walmart requirements.<br>
Once the changes are applied, Walmart Item will become Active automatically.
HTML;
            $msg = '<p>' . __($message) . '</p>';
            if (empty($msg)) {
                return $priceValue;
            }

            $priceValue .= <<<HTML
<span class="fix-magento-tooltip">
    {$this->getTooltipHtml($message, 'map_link_defected_message_icon_' . $row->getId())}
</span>
HTML;

            return $priceValue;
        }

        if ($row->getData('is_variation_parent')) {
            $onlinePriceStr = '<span style="color: #f00;">0</span>';
            if (!empty($onlineMaxPrice)) {
                $onlineMinPriceStr = $this->convertAndFormatPriceCurrency($onlineMinPrice, $currency);
                $onlineMaxPriceStr = $this->convertAndFormatPriceCurrency($onlineMaxPrice, $currency);

                if ($isExport) {
                    if ($onlineMinPrice != $onlineMaxPrice) {
                        return $onlineMinPriceStr . ' - ' . $onlineMaxPriceStr;
                    }

                    return $onlineMinPriceStr;
                }

                $onlinePriceStr = $onlineMinPriceStr
                    . (($onlineMinPrice != $onlineMaxPrice) ? ' - ' . $onlineMaxPriceStr : '');
            }

            if ($isExport) {
                return 0;
            }

            return $onlinePriceStr;
        }

        $onlinePrice = $row->getData('online_price');
        if ((float)$onlinePrice <= 0) {
            if ($isExport) {
                return 0;
            }

            $priceValue = '<span style="color: #f00;">0</span>';
        } else {
            $priceValue = $this->convertAndFormatPriceCurrency($onlinePrice, $currency);

            if ($isExport) {
                return $priceValue;
            }
        }

        $resultHtml = '';

        if (empty($resultHtml)) {
            $resultHtml = $priceValue;
        }

        $isSetOnlinePromotions = (bool)$row->getData('is_set_online_promotions');
        if ($isSetOnlinePromotions) {
            $promotionTooltipText = __('Price without promotions<br>Actual price is available on Walmart.');
            $promotionTooltipHtml = $this->getTooltipHtml(
                $promotionTooltipText,
                '',
                ['m2epro-field-tooltip-price-info']
            );
            $resultHtml = $promotionTooltipHtml . '&nbsp;' . $resultHtml;
        }

        return $resultHtml;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        if ($isExport) {
            return $value;
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Status $status */
        $status = $this->getLayout()
                       ->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Grid\Column\Renderer\Status::class);
        $status->setParentAndChildReviseScheduledCache($this->parentAndChildReviseScheduledCache);

        return $status->render($row);
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute' => 'sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'name', 'like' => '%' . $value . '%'],
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

        $where = 'status <> ' . Product::STATUS_BLOCKED;

        if (isset($value['from']) && $value['from'] != '') {
            $where .= ' AND online_qty >= ' . (int)$value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            $where .= ' AND online_qty <= ' . (int)$value['to'];
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
        $min_online_price = 'IF(
               `indexer`.`min_price` IS NULL,
               `wlp`.`online_price`,
               `indexer`.`min_price`
           )';
        $max_online_price = 'IF(
                `indexer`.`max_price` IS NULL,
                `wlp`.`online_price`,
                `indexer`.`max_price`
            )';

        if (isset($value['from']) || isset($value['to'])) {
            if (isset($value['from']) && $value['from'] != '') {
                $condition = $min_online_price . ' >= \'' . (float)$value['from'] . '\'';
            }
            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= $min_online_price . ' <= \'' . (float)$value['to'] . '\'';
            }

            $condition = '((' . $condition . ') OR (';

            if (isset($value['from']) && $value['from'] != '') {
                $condition .= $max_online_price . ' >= \'' . (float)$value['from'] . '\'';
            }
            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= $max_online_price . ' <= \'' . (float)$value['to'] . '\'';
            }

            $condition .= ')) AND status <> ' . Product::STATUS_BLOCKED;
        }

        $collection->getSelect()->where($condition);
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
            $collectionVariationParent->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
            $collectionVariationParent->getSelect()->columns(['second_table.variation_parent_id']);

            $variationParentIds = $collectionVariationParent->getColumnValues('variation_parent_id');

            $collection->addFieldToFilter(
                [
                    ['attribute' => $index, 'eq' => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED],
                    ['attribute' => 'id', 'in' => $variationParentIds],
                ]
            )->addFieldToFilter('is_online_price_invalid', 0);
        }
    }

    public function getRowUrl($item)
    {
        return false;
    }

    public function getTooltipHtml($content, $id = '', $classes = [])
    {
        $classes = implode(' ', $classes);

        return <<<HTML
    <div id="{$id}" class="m2epro-field-tooltip admin__field-tooltip {$classes}">
        <a class="admin__field-tooltip-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content" style="width:300px">
            {$content}
        </div>
    </div>
HTML;
    }

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

    private function convertAndFormatPriceCurrency($price, $currency)
    {
        return $this->localeCurrency->getCurrency($currency)->toCurrency($price);
    }
}
