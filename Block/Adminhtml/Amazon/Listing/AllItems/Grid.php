<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AllItems;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Product\Grid
{
    private const ACTUAL_QTY_EXPRESSION = 'IF(alp.is_afn_channel = 1, alp.online_afn_qty, alp.online_qty)';

    private \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory;
    private \Magento\Framework\Locale\CurrencyInterface $localeCurrency;
    private \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory;
    private \Ess\M2ePro\Helper\Component\Amazon $amazonHelper;
    private array $parentAndChildReviseScheduledCache = [];
    private \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper;
    private \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation $tagRelationResource;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource;
    private \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource;
    private \Ess\M2ePro\Model\ResourceModel\Listing $listingResource;
    private \Ess\M2ePro\Model\ResourceModel\Amazon\Listing $amazonListingResource;
    private \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Repricing $amazonListingProductRepricingResource;
    private \Ess\M2ePro\Model\Amazon\AdvancedFilter\AllItemsOptions $advancedFilterAllItemsOptions;
    private \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\CollectionFactory $amazonProductCollectionFactory;
    private \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\FilterFactory $advancedFilterFactory;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\FilterFactory $advancedFilterFactory,
        \Ess\M2ePro\Model\Amazon\AdvancedFilter\AllItemsOptions $advancedFilterAllItemsOptions,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing $amazonListingResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Repricing $amazonListingProductRepricingResource,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\CollectionFactory $amazonProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation $tagRelationResource,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $dataHelper, $data);

        $this->databaseHelper = $databaseHelper;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->localeCurrency = $localeCurrency;
        $this->amazonFactory = $amazonFactory;
        $this->amazonHelper = $amazonHelper;
        $this->tagRelationResource = $tagRelationResource;
        $this->listingProductResource = $listingProductResource;
        $this->amazonListingProductResource = $amazonListingProductResource;
        $this->listingResource = $listingResource;
        $this->amazonListingResource = $amazonListingResource;
        $this->amazonListingProductRepricingResource = $amazonListingProductRepricingResource;
        $this->advancedFilterAllItemsOptions = $advancedFilterAllItemsOptions;
        $this->amazonProductCollectionFactory = $amazonProductCollectionFactory;
        $this->advancedFilterFactory = $advancedFilterFactory;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonListingAllItemsGrid');

        $this->setDefaultSort(false);
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);

        $this->showAdvancedFilterProductsOption = false;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        $this->getMassactionBlock()->addItem('list', [
            'label' => __('List Item(s) on Amazon'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('revise', [
            'label' => __('Revise Item(s) on Amazon'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('relist', [
            'label' => __('Relist Item(s) on Amazon'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('stop', [
            'label' => __('Stop Item(s) on Amazon'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('stopAndRemove', [
            'label' => __('Stop on Amazon / Remove From Listing'),
            'url' => '',
        ], 'actions');

        return parent::_prepareMassaction();
    }

    protected function _prepareColumns()
    {
        $this->addExportType('*/*/exportCsvAllItemsGrid', __('CSV'));

        $this->addColumn('entity_id', [
            'header' => __('Product ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'filter_index' => 'entity_id',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
            'filter_condition_callback' => [$this, 'callbackFilterProductId'],
        ]);

        $this->addColumn('name', [
            'header' => __('Product Title / Listing / Product SKU'),
            'header_export' => __('Product SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        $this->addColumn('online_sku', [
            'header' => __('SKU'),
            'header_export' => __('Amazon SKU'),
            'align' => 'left',
            'width' => '150px',
            'type' => 'text',
            'index' => 'online_sku',
            'filter_index' => 'online_sku',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Renderer\Sku::class,
            'show_defected_messages' => false,
            'filter_condition_callback' => [$this, 'callbackFilterOnlineSku'],
        ]);

        $this->addColumn('general_id', [
            'header' => __('ASIN / ISBN'),
            'align' => 'left',
            'width' => '100px',
            'type' => 'text',
            'index' => 'general_id',
            'filter_index' => 'general_id',
            'frame_callback' => [$this, 'callbackColumnGeneralId'],
            'filter' => \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\GeneralId::class,
            'filter_condition_callback' => [$this, 'callbackFilterAsinIsbn'],
        ]);

        $this->addColumn('online_qty', [
            'header' => __('QTY'),
            'align' => 'right',
            'width' => '70px',
            'type' => 'number',
            'index' => 'online_actual_qty',
            'filter_index' => 'online_actual_qty',
            'frame_callback' => [$this, 'callbackColumnAvailableQty'],
            'filter' => \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Qty::class,
            'filter_condition_callback' => [$this, 'callbackFilterQty'],
        ]);

        $priceColumn = [
            'header' => __('Price'),
            'align' => 'right',
            'width' => '110px',
            'type' => 'number',
            'index' => 'online_current_price',
            'filter_index' => 'online_current_price',
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice'],
        ];

        if ($this->activeRecordFactory->getObject('Amazon_Account_Repricing')->getCollection()->getSize() > 0) {
            $priceColumn['filter'] = \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Price::class;
        }

        $this->addColumn('online_price', $priceColumn);

        $statusColumn = [
            'header' => __('Status'),
            'width' => '125px',
            'index' => 'amazon_status',
            'filter_index' => 'amazon_status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN => __('Unknown'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => __('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => __('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE => __('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => __('Incomplete'),
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
            'filter_condition_callback' => [$this, 'callbackFilterStatus'],
        ];

        $listingType = $this->getRequest()->getParam(
            'listing_type',
            \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_M2E_PRO
        );

        if ($listingType == \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_LISTING_OTHER) {
            unset($statusColumn['options'][\Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED]);
        }

        $this->addColumn('amazon_status', $statusColumn);

        $this->addColumn('goto_listing_item', [
            'header' => __('Manage'),
            'align' => 'center',
            'width' => '80px',
            'type' => 'text',
            'filter' => false,
            'sortable' => false,
            'frame_callback' => [$this, 'callbackColumnActions'],
            'is_system' => true,
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareAdvancedFilters()
    {
        $this->addMarketplaceAdvancedFilter();
        $this->addAccountAdvancedFilter();

        $this->addSellingPolicyAdvancedFilter();
        $this->addSynchronizationPolicyAdvancedFilter();
        $this->addShippingPolicyAdvancedFilter();
        $this->addTaxCodePolicyAdvancedFilter();

        $this->addProductTypeAdvancedFilter();

        $this->addErrorsAdvancedFilter();
        $this->addMagentoProductTypeAdvancedFilter();

        parent::_prepareAdvancedFilters();
    }

    protected function _prepareCollection()
    {
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->getSelect()->distinct();
        $collection->setListingProductModeOn();

        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');

        $collection->joinTable(
            ['lp' => $this->listingProductResource->getMainTable()],
            'product_id = entity_id',
            [
                'id' => 'id',
                'amazon_status' => 'status',
                'component_mode' => 'component_mode',
                'listing_id' => 'listing_id',
                'additional_data' => 'additional_data',
            ]
        );

        $collection->addExpressionAttributeToSelect(
            'online_actual_qty',
            self::ACTUAL_QTY_EXPRESSION,
            []
        );

        $collection->joinTable(
            ['alp' => $this->amazonListingProductResource->getMainTable()],
            'listing_product_id = id',
            [
                'listing_product_id' => 'listing_product_id',
                'is_general_id_owner' => 'is_general_id_owner',
                'general_id' => 'general_id',
                'is_repricing' => 'is_repricing',
                'is_afn_channel' => 'is_afn_channel',
                'variation_parent_id' => 'variation_parent_id',
                'is_variation_parent' => 'is_variation_parent',
                'variation_child_statuses' => 'variation_child_statuses',
                'online_sku' => 'sku',
                'online_qty' => 'online_qty',
                'online_afn_qty' => 'online_afn_qty',
                'online_regular_price' => 'online_regular_price',
                'online_regular_sale_price' => 'online_regular_sale_price',
                'online_regular_sale_price_start_date' => 'online_regular_sale_price_start_date',
                'online_regular_sale_price_end_date' => 'online_regular_sale_price_end_date',

                'online_business_price' => 'online_business_price',

                'variation_parent_afn_state' => 'variation_parent_afn_state',
                'variation_parent_repricing_state' => 'variation_parent_repricing_state',

                'online_current_price' => new \Zend_Db_Expr(
                    'IF(
                    alp.online_regular_sale_price_start_date IS NOT NULL AND
                    alp.online_regular_sale_price_end_date IS NOT NULL AND
                    alp.online_regular_sale_price_start_date <= CURRENT_DATE() AND
                    alp.online_regular_sale_price_end_date >= CURRENT_DATE(),
                    alp.online_regular_sale_price,
                    alp.online_regular_price
                )'
                ),
            ],
            'variation_parent_id IS NULL'
        );
        $collection->joinTable(
            ['l' => $this->listingResource->getMainTable()],
            'id=listing_id',
            [
                'store_id' => 'store_id',
                'account_id' => 'account_id',
                'marketplace_id' => 'marketplace_id',
                'listing_title' => 'title',
            ]
        );
        $collection->getSelect()->joinLeft(
            ['al' => $this->amazonListingResource->getMainTable()],
            'l.id = al.listing_id',
            null
        );

        $collection->joinTable(
            ['malpr' => $this->amazonListingProductRepricingResource->getMainTable()],
            'listing_product_id=listing_product_id',
            [
                'is_repricing_disabled' => 'is_online_disabled',
            ],
            null,
            'left'
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * If child variation product has tag, then select parent product.
     * And select not variation products with tag.
     */
    private function getSubSelectForAmazonListingProductsWithTags(int $tagId): \Magento\Framework\DB\Select
    {
        $collection = $this->amazonProductCollectionFactory->create();
        $collection->distinct(true);
        $collection->join(
            ['tag_rel' => $this->tagRelationResource->getMainTable()],
            'main_table.listing_product_id = tag_rel.listing_product_id'
        );

        $collection->getSelect()->where('tag_rel.tag_id = ?', $tagId);

        $collection->getSelect()->reset('columns');
        $collection->getSelect()->columns(
            new \Zend_Db_Expr('IFNULL(main_table.variation_parent_id, main_table.listing_product_id)')
        );

        return $collection->getSelect();
    }

    protected function _afterLoadCollection()
    {
        $collection = $this->amazonFactory->getObject('Listing_Product')->getCollection();
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

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        if ($isExport) {
            return $row->getData('sku');
        }

        $sku = $this->dataHelper->escapeHtml($row->getData('sku'));

        $title = $row->getData('name');
        $title = $this->dataHelper->escapeHtml($title);

        $listingWord = __('Listing');
        $listingTitle = $this->dataHelper->escapeHtml($row->getData('listing_title'));
        $listingTitle = $this->filterManager->truncate($listingTitle, ['length' => 50]);

        $listingUrl = $this->getUrl(
            '*/amazon_listing/view',
            ['id' => $row->getData('listing_id')]
        );

        $value = <<<HTML
<div style="margin-bottom: 5px">{$title}</div>
<strong>{$listingWord}:</strong>&nbsp;
<a href="{$listingUrl}" target="_blank">{$listingTitle}</a>
HTML;

        $account = $this->amazonFactory->getCachedObjectLoaded('Account', $row->getData('account_id'));
        $marketplace = $this->amazonFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

        $value .= '<br/><strong>' . __('Account') . ':</strong>'
            . '&nbsp;' . $account->getTitle() . '<br/>'
            . '<strong>' . __('Marketplace') . ':</strong>'
            . '&nbsp;' . $marketplace->getTitle();

        $skuWord = __('SKU');

        $value .= <<<HTML
<br/><strong>{$skuWord}:</strong>&nbsp;
<span class="white-space-pre-wrap">{$sku}</span>
HTML;

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if ($variationManager->isVariationParent()) {
            $productAttributes = $variationManager->getTypeModel()->getProductAttributes();

            $virtualProductAttributes = $variationManager->getTypeModel()->getVirtualProductAttributes();
            $virtualChannelAttributes = $variationManager->getTypeModel()->getVirtualChannelAttributes();

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

            $value .= <<<HTML
<div style="font-size: 11px; font-weight: bold; color: grey; margin-top: 15px">
    {$attributesStr}
</div>
HTML;
        }

        if (
            $variationManager->isIndividualType() &&
            $variationManager->getTypeModel()->isVariationProductMatched()
        ) {
            $optionsStr = '';
            $productOptions = $variationManager->getTypeModel()->getProductOptions();

            foreach ($productOptions as $attribute => $option) {
                $attribute = $this->dataHelper->escapeHtml($attribute);
                if ($option === '' || $option === null) {
                    $option = '--';
                }
                $option = $this->dataHelper->escapeHtml($option);

                $optionsStr .= <<<HTML
<strong>{$attribute}</strong>:&nbsp;{$option}<br/>
HTML;
            }

            $value .= <<<HTML
<br/>
<div style="font-size: 11px; color: grey;">
    {$optionsStr}
</div>
<br/>
HTML;
        }

        return $value;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        if ($isExport) {
            return $value;
        }

        $value = $this->getProductStatus($row->getData('amazon_status'));

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if (!$variationManager->isVariationParent()) {
            return $value . $this->getScheduledTag($row) . $this->getLockedTag($row);
        }

        $html = '';

        $sUnknown = \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;
        $sNotListed = \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;
        $sListed = \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
        $sInactive = \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE;
        $sBlocked = \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED;

        $generalId = $listingProduct->getChildObject()->getGeneralId();
        $variationsStatuses = $row->getData('variation_child_statuses');

        if (empty($generalId) || empty($variationsStatuses)) {
            return $this->getProductStatus($sNotListed) . $this->getScheduledTag($row) . $this->getLockedTag($row);
        }

        $sortedStatuses = [];
        $variationsStatuses = \Ess\M2ePro\Helper\Json::decode($variationsStatuses);

        isset($variationsStatuses[$sUnknown]) && $sortedStatuses[$sUnknown] = $variationsStatuses[$sUnknown];
        isset($variationsStatuses[$sNotListed]) && $sortedStatuses[$sNotListed] = $variationsStatuses[$sNotListed];
        isset($variationsStatuses[$sListed]) && $sortedStatuses[$sListed] = $variationsStatuses[$sListed];
        isset($variationsStatuses[$sInactive]) && $sortedStatuses[$sInactive] = $variationsStatuses[$sInactive];
        isset($variationsStatuses[$sBlocked]) && $sortedStatuses[$sBlocked] = $variationsStatuses[$sBlocked];

        foreach ($sortedStatuses as $status => $productsCount) {
            if (empty($productsCount)) {
                continue;
            }

            $productsCount = '[' . $productsCount . ']';
            $html .= $this->getProductStatus($status) . '&nbsp;' . $productsCount . '<br/>';
        }

        return $html . $this->getScheduledTag($row) . $this->getLockedTag($row);
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $productId = (int)$row->getData('entity_id');

        $urlData = [
            'id' => $row->getData('listing_id'),
            'filter' => base64_encode("product_id[from]={$productId}&product_id[to]={$productId}"),
        ];

        $searchedChildHtml = '';
        if ($this->wasFoundByChild($row)) {
            $urlData['child_listing_product_ids'] = $this->getChildListingProductIds($row);

            $searchedChildHtml = <<<HTML
<br/>
<div class="fix-magento-tooltip searched_child_product" style="margin-top: 4px; padding-left: 10px;">
    {$this->getTooltipHtml(
                __(
                    'A Product you are searching for is found as part of a Multi-Variational Product.' .
                    ' Click on the arrow icon to manage it individually.'
                )
            )}
</div>
HTML;
        }
        $manageUrl = $this->getUrl('*/amazon_listing/view/', $urlData);
        $html = <<<HTML
<div style="float:right; margin:5px 15px 0 0;">
    <a title="{$this->__('Go to Listing')}" target="_blank" href="{$manageUrl}">
    <img src="{$this->getViewFileUrl('Ess_M2ePro::images/goto_listing.png')}" alt="{$this->__('Go to Listing')}" /></a>
</div>
HTML;

        return $searchedChildHtml . $html;
    }

    protected function getLockedTag($row)
    {
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProductId = (int)$row->getData('listing_product_id');
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        $tempLocks = $listingProduct->getProcessingLocks();

        $html = '';
        $childCount = 0;

        foreach ($tempLocks as $lock) {
            switch ($lock->getTag()) {
                case 'list_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('List in Progress')
                        . '...]</span>';
                    break;

                case 'relist_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('Relist in Progress')
                        . '...]</span>';
                    break;

                case 'revise_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('Revise in Progress')
                        . '...]</span>';
                    break;

                case 'stop_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('Stop in Progress')
                        . '...]</span>';
                    break;

                case 'stop_and_remove_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('Stop And Remove in Progress')
                        . '...]</span>';
                    break;

                case 'delete_and_remove_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('Remove in Progress')
                        . '...]</span>';
                    break;

                case 'switch_to_afn_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('Switch to AFN in Progress')
                        . '...]</span>';
                    break;

                case 'switch_to_mfn_action':
                    $html .= '<br/><span style="color: #605fff">'
                        . __('Switch to MFN in Progress')
                        . '...]</span>';
                    break;

                case 'child_products_in_action':
                    $childCount++;
                    break;

                default:
                    break;
            }
        }

        if ($childCount > 0) {
            $html .= '<br/><span style="color: #605fff">[' . __('Child(s) in Action') . '...]</span>';
        }

        return $html;
    }

    protected function getScheduledTag($row)
    {
        $html = '';

        /**
         * @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction\Collection $scheduledActionsCollection
         */
        $scheduledActionsCollection = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction')
                                                                ->getCollection();
        $scheduledActionsCollection->addFieldToFilter('listing_product_id', $row['id']);

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction */
        $scheduledAction = $scheduledActionsCollection->getFirstItem();

        if (!$scheduledAction->getId()) {
            return $html;
        }

        switch ($scheduledAction->getActionType()) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                $html .= '<br/><span style="color: #605fff">[List is Scheduled...]</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST:
                $html .= '<br/><span style="color: #605fff">[Relist is Scheduled...]</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE:
                $reviseParts = [];

                $additionalData = $scheduledAction->getAdditionalData();
                if (
                    !empty($additionalData['configurator']) &&
                    !isset($this->parentAndChildReviseScheduledCache[$row->getData('id')])
                ) {
                    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator */
                    $configurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');
                    $configurator->setUnserializedData($additionalData['configurator']);

                    if ($configurator->isIncludingMode()) {
                        if ($configurator->isQtyAllowed()) {
                            $reviseParts[] = 'QTY';
                        }

                        if ($configurator->isRegularPriceAllowed() || $configurator->isBusinessPriceAllowed()) {
                            $reviseParts[] = 'Price';
                        }

                        if ($configurator->isDetailsAllowed()) {
                            $reviseParts[] = 'Details';
                        }
                    }
                }

                if (!empty($reviseParts)) {
                    $html .= '<br/><span style="color: #605fff">[Revise of ' . implode(', ', $reviseParts)
                        . ' is Scheduled...]</span>';
                } else {
                    $html .= '<br/><span style="color: #605fff">[Revise is Scheduled...]</span>';
                }
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_STOP:
                $html .= '<br/><span style="color: #605fff">[Stop is Scheduled...]</span>';
                break;

            case \Ess\M2ePro\Model\Listing\Product::ACTION_DELETE:
                $html .= '<br/><span style="color: #605fff">[Delete is Scheduled...]</span>';
                break;

            default:
                break;
        }

        return $html;
    }

    protected function callbackFilterProductId($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $childCollection = $this->getMagentoChildProductsCollection();
        $childCollection->addFieldToFilter('product_id', $cond);

        $collection->getSelect()->joinLeft(
            ['product_id_subQuery' => $childCollection->getSelect()],
            'product_id_subQuery.variation_parent_id=lp.id',
            [
                'product_id_child_listing_product_ids' => 'child_listing_product_ids',
                'product_id_searched_by_child' => 'searched_by_child',
            ]
        );

        $collection->addFieldToFilter([
            ['attribute' => 'entity_id', $cond],
            ['attribute' => 'product_id_subQuery.searched_by_child', 'eq' => '1', 'raw' => true],
        ]);
    }

    protected function callbackFilterTitle($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $childCollection = $this->getMagentoChildProductsCollection();
        $childCollection->getSelect()->joinLeft(
            [
                'cpe' => $this->databaseHelper
                    ->getTableNameWithPrefix('catalog_product_entity'),
            ],
            'cpe.entity_id=main_table.product_id',
            []
        );
        $childCollection->addFieldToFilter('cpe.sku', ['like' => '%' . $value . '%']);

        $collection->getSelect()->joinLeft(
            ['product_sku_subQuery' => $childCollection->getSelect()],
            'product_sku_subQuery.variation_parent_id=lp.id',
            [
                'product_sku_child_listing_product_ids' => 'child_listing_product_ids',
                'product_sku_searched_by_child' => 'searched_by_child',
            ]
        );

        $collection->addFieldToFilter(
            [
                ['attribute' => 'sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'name', 'like' => '%' . $value . '%'],
                ['attribute' => 'listing_title', 'like' => '%' . $value . '%'],
                ['attribute' => 'product_sku_subQuery.searched_by_child', 'eq' => '1', 'raw' => true],
            ]
        );
    }

    protected function callbackFilterOnlineSku($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $childCollection = $this->getChildProductsCollection();
        $childCollection->addFieldToFilter('sku', ['like' => '%' . $value . '%']);

        $collection->getSelect()->joinLeft(
            ['online_sku_subQuery' => $childCollection->getSelect()],
            'online_sku_subQuery.variation_parent_id=lp.id',
            [
                'online_sku_child_listing_product_ids' => 'child_listing_product_ids',
                'online_sku_searched_by_child' => 'searched_by_child',
            ]
        );

        $collection->addFieldToFilter([
            ['attribute' => 'online_sku', 'like' => '%' . $value . '%'],
            ['attribute' => 'online_sku_subQuery.searched_by_child', 'eq' => '1', 'raw' => true],
        ]);
    }

    protected function callbackFilterAsinIsbn($collection, $column)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $value = $column->getFilter()->getValue();
        if (empty($value)) {
            return;
        }

        $childCollection = $this->getChildProductsCollection();

        $inputValue = $column->getFilter()->getValue('input');
        if ($inputValue !== null) {
            $childCollection->addFieldToFilter('general_id', ['like' => '%' . $inputValue . '%']);
            $collection->addFieldToFilter([
                ['attribute' => 'general_id', 'like' => '%' . $inputValue . '%'],
                ['attribute' => 'asin_subQuery.searched_by_child', 'eq' => '1', 'raw' => true],
            ]);
        }

        $selectValue = $column->getFilter()->getValue('select');
        if ($selectValue !== null) {
            $childCollection->addFieldToFilter('is_general_id_owner', $selectValue);
            $collection->addFieldToFilter('is_general_id_owner', $selectValue);
        }

        $collection->getSelect()->joinLeft(
            ['asin_subQuery' => $childCollection->getSelect()],
            'asin_subQuery.variation_parent_id=lp.id',
            [
                'asin_child_listing_product_ids' => 'child_listing_product_ids',
                'asin_searched_by_child' => 'searched_by_child',
            ]
        );
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $onlineCurrentPrice = 'IF (
            alp.online_regular_price IS NULL,
            alp.online_business_price,
            IF(
                alp.online_regular_sale_price_start_date IS NOT NULL AND
                alp.online_regular_sale_price_end_date IS NOT NULL AND
                alp.online_regular_sale_price_start_date <= CURRENT_DATE() AND
                alp.online_regular_sale_price_end_date >= CURRENT_DATE(),
                alp.online_regular_sale_price,
                alp.online_regular_price
            )
        )';

        $where = '';

        if (isset($cond['from']) || isset($cond['to'])) {
            if (isset($cond['from']) && $cond['from'] != '') {
                $value = $collection->getConnection()->quote($cond['from']);
                $where .= "{$onlineCurrentPrice} >= {$value}";
            }

            if (isset($cond['to']) && $cond['to'] != '') {
                if (isset($cond['from']) && $cond['from'] != '') {
                    $where .= ' AND ';
                }
                $value = $collection->getConnection()->quote($cond['to']);
                $where .= "{$onlineCurrentPrice} <= {$value}";
            }
        }

        if (isset($cond['is_repricing'])) {
            if (!empty($where)) {
                $where = '(' . $where . ') OR ';
            }

            if ((int)$cond['is_repricing'] == 1) {
                $where .= 'alp.is_repricing = 1';
            } else {
                $partialFilter = \Ess\M2ePro\Model\Amazon\Listing\Product::VARIATION_PARENT_IS_REPRICING_STATE_PARTIAL;
                $where .= "(alp.is_repricing = 0 OR alp.variation_parent_repricing_state = {$partialFilter})";
            }
        }

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
            $quoted = $collection->getConnection()->quote($value['from']);
            $where .= self::ACTUAL_QTY_EXPRESSION . ' >= ' . $quoted;
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }
            $quoted = $collection->getConnection()->quote($value['to']);
            $where .= self::ACTUAL_QTY_EXPRESSION . ' <= ' . $quoted;
        }

        if (isset($value['afn']) && $value['afn'] !== '') {
            if (!empty($where)) {
                $where .= ' AND ';
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

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            "status = {$value} OR (variation_child_statuses REGEXP '\"{$value}\":[^0]') AND is_variation_parent = 1"
        );
    }

    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if ($collection) {
            $columnIndex = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();

            if ($columnIndex == 'online_current_price') {
                $onlineCurrentPrice = 'IF(
                    alp.online_regular_sale_price_start_date IS NOT NULL AND
                    alp.online_regular_sale_price_end_date IS NOT NULL AND
                    alp.online_regular_sale_price_start_date <= CURRENT_DATE() AND
                    alp.online_regular_sale_price_end_date >= CURRENT_DATE(),
                    alp.online_regular_sale_price,
                    alp.online_regular_price
                )';
                $collection->getSelect()->order(
                    $onlineCurrentPrice . ' ' . strtoupper($column->getDir())
                );
            } else {
                $collection->setOrder($columnIndex, strtoupper($column->getDir()));
            }
        }

        return $this;
    }

    protected function getMagentoChildProductsCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\Option\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')->getCollection()
                                                ->addFieldToSelect('listing_product_variation_id')
                                                ->addFieldToFilter(
                                                    'main_table.component_mode',
                                                    \Ess\M2ePro\Helper\Component\Amazon::NICK
                                                );

        $collection->getSelect()->joinLeft(
            [
                'lpv' => $this->activeRecordFactory->getObject('Listing_Product_Variation')
                                                   ->getResource()->getMainTable(),
            ],
            'lpv.id=main_table.listing_product_variation_id',
            ['listing_product_id']
        );
        $collection->getSelect()->joinLeft(
            ['alp' => $this->activeRecordFactory->getObject('Amazon_Listing_Product')->getResource()->getMainTable()],
            'alp.listing_product_id=lpv.listing_product_id',
            ['variation_parent_id']
        );
        $collection->addFieldToFilter('variation_parent_id', ['notnull' => true]);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'child_listing_product_ids' => new \Zend_Db_Expr('GROUP_CONCAT(DISTINCT alp.listing_product_id)'),
                'variation_parent_id' => 'alp.variation_parent_id',
                'searched_by_child' => new \Zend_Db_Expr('1'),
            ]
        );

        $collection->getSelect()->group('alp.variation_parent_id');

        return $collection;
    }

    protected function getChildProductsCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Collection $collection */
        $collection = $this->activeRecordFactory->getObject('Amazon_Listing_Product')->getCollection()
                                                ->addFieldToFilter('variation_parent_id', ['notnull' => true])
                                                ->addFieldToFilter('is_variation_product', 1);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'child_listing_product_ids' => new \Zend_Db_Expr('GROUP_CONCAT(listing_product_id)'),
                'variation_parent_id' => 'variation_parent_id',
                'searched_by_child' => new \Zend_Db_Expr('1'),
            ]
        );
        $collection->getSelect()->group('variation_parent_id');

        return $collection;
    }

    protected function wasFoundByChild($row)
    {
        foreach (['product_id', 'product_sku', 'online_sku', 'asin'] as $item) {
            $searchedByChild = $row->getData("{$item}_searched_by_child");
            if (!empty($searchedByChild)) {
                return true;
            }
        }

        return false;
    }

    protected function getChildListingProductIds($row)
    {
        $ids = [];

        foreach (['product_id', 'product_sku', 'online_sku', 'asin'] as $item) {
            $itemIds = $row->getData("{$item}_child_listing_product_ids");
            if (empty($itemIds)) {
                continue;
            }

            foreach (explode(',', $itemIds) as $itemId) {
                !isset($ids[$itemId]) && $ids[$itemId] = 0;
                $ids[$itemId]++;
            }
        }

        $maxCount = max($ids);
        foreach ($ids as $id => $count) {
            if ($count < $maxCount) {
                unset($ids[$id]);
            }
        }

        return implode(',', array_keys($ids));
    }

    public function callbackColumnGeneralId($value, $row, $column, $isExport)
    {
        if ($isExport) {
            return $value;
        }

        if (empty($value)) {
            if ((int)$row->getData('amazon_status') != \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                return '<i style="color:gray;">' . __('receiving...') . '</i>';
            }

            if ($row->getData('is_general_id_owner')) {
                return __('New ASIN/ISBN');
            }

            return __('N/A');
        }

        $url = $this->amazonHelper->getItemUrl($value, $row->getData('marketplace_id'));

        return '<a href="' . $url . '" target="_blank">' . $value . '</a>';
    }

    public function callbackColumnAvailableQty($value, $row, $column, $isExport)
    {
        if ($row->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED) {
            if ($isExport) {
                return '';
            }

            return __('N/A');
        }

        if (!$row->getData('is_variation_parent')) {
            if ($row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                if ($isExport) {
                    return '';
                }

                return '<span style="color: gray;">' . __('Not Listed') . '</span>';
            }

            if ($row->getData('is_afn_channel')) {
                $qty = $row->getData('online_afn_qty') ?? __('N/A');

                $imageURL = $this->getViewFileUrl('Ess_M2ePro::images/amazon-afn-icon.svg');

                return "<span class='fba-qty-column'><img src='$imageURL' alt='fba'> $qty</span>";
            }

            if ($value === null || $value === '') {
                if ($isExport) {
                    return '';
                }

                return '<i style="color:gray;">receiving...</i>';
            }

            if ($value <= 0) {
                if ($isExport) {
                    return 0;
                }

                return '<span style="color: red;">0</span>';
            }

            return $value;
        }

        if ($row->getData('general_id') == '') {
            if ($isExport) {
                return '';
            }

            return '<span style="color: gray;">' . __('Not Listed') . '</span>';
        }

        $variationChildStatuses = \Ess\M2ePro\Helper\Json::decode($row->getData('variation_child_statuses'));

        if (empty($variationChildStatuses)) {
            if ($isExport) {
                return '';
            }

            return __('N/A');
        }

        $activeChildrenCount = 0;
        foreach ($variationChildStatuses as $childStatus => $count) {
            if ($childStatus == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                continue;
            }
            $activeChildrenCount += (int)$count;
        }

        if ($activeChildrenCount == 0) {
            if ($isExport) {
                return '';
            }

            return __('N/A');
        }

        if (!(bool)$row->getData('is_afn_channel')) {
            return $value;
        }

        if ($isExport) {
            return $value;
        }

        $resultValue = __('AFN');
        $additionalData = (array)\Ess\M2ePro\Helper\Json::decode($row->getData('additional_data'));

        if (!empty($additionalData['afn_count'])) {
            $resultValue = $resultValue . "&nbsp;[" . $additionalData['afn_count'] . "]";
        }

        return <<<HTML
    <div>{$value}</div>
    <div>{$resultValue}</div>
HTML;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        if ($row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED) {
            if ($isExport) {
                return '';
            }

            return __('N/A');
        }

        if (
            (!$row->getData('is_variation_parent') &&
                $row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) ||
            ($row->getData('is_variation_parent') && $row->getData('general_id') == '')
        ) {
            if ($isExport) {
                return '';
            }

            return '<span style="color: gray;">' . __('Not Listed') . '</span>';
        }

        $repricingHtml = '';

        if ($row->getData('is_repricing')) {
            if ($row->getData('is_variation_parent')) {
                $additionalData = (array)\Ess\M2ePro\Helper\Json::decode($row->getData('additional_data'));

                $enabledCount = isset($additionalData['repricing_managed_count'])
                    ? $additionalData['repricing_managed_count'] : null;

                $disabledCount = isset($additionalData['repricing_not_managed_count'])
                    ? $additionalData['repricing_not_managed_count'] : null;

                if ($enabledCount && $disabledCount) {
                    $icon = 'repricing-enabled-disabled';
                    $countHtml = '[' . $enabledCount . '/' . $disabledCount . ']';
                    $text = __(
                        'This Parent has either Enabled and Disabled for dynamic repricing Child Products. <br>
                        <strong>Please note</strong> that the Price value(s) shown in the grid might be
                        different from the actual one from Amazon. It is caused by the delay in the values
                        updating made via the Repricing Service.'
                    );
                } elseif ($enabledCount) {
                    $icon = 'repricing-enabled';
                    $countHtml = '[' . $enabledCount . ']';
                    $text = __(
                        'All Child Products of this Parent are Enabled for dynamic repricing. <br>
                        <strong>Please note</strong> that the Price value(s) shown in the grid might be different
                        from the actual one from Amazon. It is caused by the delay in the values updating
                        made via the Repricing Service.'
                    );
                } elseif ($disabledCount) {
                    $icon = 'repricing-disabled';
                    $countHtml = '[' . $disabledCount . ']';
                    $text = __('All Child Products of this Parent are Disabled for Repricing.');
                } else {
                    $icon = 'repricing-enabled';
                    $countHtml = __('[-/-]');
                    $text = __(
                        'Some Child Products of this Parent are managed by the Repricing Service. <br>
                        <strong>Please note</strong> that the Price value(s) shown in the grid might be
                        different from the actual one from Amazon. It is caused by the delay in the
                        values updating made via the Repricing Service.'
                    );
                }

                $repricingHtml = <<<HTML
<br/>
<div class="fix-magento-tooltip {$icon}">
    {$this->getTooltipHtml($text)}
</div>
    &nbsp;$countHtml&nbsp;
HTML;
            } elseif (!$row->getData('is_variation_parent')) {
                $icon = 'repricing-enabled';
                $text = __(
                    'This Product is used by Amazon Repricing Tool, so its Price cannot be managed via M2E Pro.<br>
                    <strong>Please note</strong> that the Price value shown in the grid might be different
                    from the actual one from Amazon. It is caused by the delay in the values
                    updating made via the Repricing Service.'
                );

                if ((int)$row->getData('is_repricing_disabled') == 1) {
                    $icon = 'repricing-disabled';

                    if ($this->getId() == 'amazonListingSearchOtherGrid') {
                        $text = __(
                            'This product is disabled on Amazon Repricing Tool. <br>
                            You can link it to Magento Product and Move into M2E Pro Listing to make the
                            Price being updated via M2E Pro.'
                        );
                    } else {
                        $text = __(
                            'This product is disabled on Amazon Repricing Tool.
                            The Price is updated through the M2E Pro.'
                        );
                    }
                }

                $repricingHtml = <<<HTML
&nbsp;<div class="fix-magento-tooltip {$icon}">
    {$this->getTooltipHtml($text)}
</div>
HTML;
            }
        }

        $currentOnlinePrice = (float)$row->getData('online_current_price');
        $onlineBusinessPrice = (float)$row->getData('online_business_price');

        if (empty($currentOnlinePrice) && empty($onlineBusinessPrice)) {
            if ($isExport) {
                return '';
            }

            if (
                $row->getData('amazon_status') == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED ||
                $row->getData('is_variation_parent')
            ) {
                return __('N/A') . $repricingHtml;
            }

            return '<i style="color:gray;">receiving...</i>' . $repricingHtml;
        }

        $marketplaceId = $row->getData('marketplace_id');
        $currency = $this->amazonFactory
            ->getCachedObjectLoaded('Marketplace', $marketplaceId)
            ->getChildObject()
            ->getDefaultCurrency();

        if ($row->getData('is_variation_parent')) {
            $noticeText = __('The value is calculated as minimum price of all Child Products.');
            $priceHtml = <<<HTML
<div class="m2epro-field-tooltip admin__field-tooltip" style="display: inline;">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        {$noticeText}
    </div>
</div>
HTML;

            if (!empty($currentOnlinePrice)) {
                $currentOnlinePrice = $this->localeCurrency->getCurrency($currency)->toCurrency($currentOnlinePrice);
                $priceHtml .= "<span>{$currentOnlinePrice}</span><br />";

                if ($isExport) {
                    return $currentOnlinePrice;
                }
            }

            if (!empty($onlineBusinessPrice)) {
                $onlineBusinessPrice = $this->localeCurrency->getCurrency($currency)->toCurrency($onlineBusinessPrice);
                $priceHtml .= '<strong>B2B:</strong> ' . $onlineBusinessPrice;

                if ($isExport) {
                    return $onlineBusinessPrice;
                }
            }

            if ($isExport) {
                return '';
            }

            return $priceHtml . $repricingHtml;
        }

        $onlinePrice = $row->getData('online_regular_price');
        if ((float)$onlinePrice <= 0) {
            $priceValue = '<span style="color: #f00;">0</span>';

            if ($isExport) {
                $priceValueExport = 0;
            }
        } else {
            $priceValue = $this->localeCurrency->getCurrency($currency)->toCurrency($onlinePrice);

            if ($isExport) {
                $priceValueExport = $priceValue;
            }
        }

        if (
            $row->getData('is_repricing') &&
            !$row->getData('is_repricing_disabled') &&
            !$row->getData('is_variation_parent')
        ) {
            $accountId = $row->getData('account_id');
            $sku = $row->getData('online_sku');

            $priceValue = <<<HTML
<a id="m2epro_repricing_price_value_{$sku}"
   class="m2epro-repricing-price-value"
   sku="{$sku}"
   account_id="{$accountId}"
   href="javascript:void(0)"
   onclick="AmazonListingProductRepricingPriceObj.showRepricingPrice()">{$priceValue}</a>
HTML;
        }

        $resultHtml = '';

        $salePrice = $row->getData('online_regular_sale_price');
        if (!$row->getData('is_variation_parent') && (float)$salePrice > 0 && !$row->getData('is_repricing')) {
            $currentTimestamp = (int)$this->dataHelper->createGmtDateTime(
                $this->dataHelper->getCurrentGmtDate(false, 'Y-m-d 00:00:00')
            )->format('U');

            $startDateTimestamp = (int)$this->dataHelper->createGmtDateTime(
                $row->getData('online_regular_sale_price_start_date')
            )->format('U');
            $endDateTimestamp = (int)$this->dataHelper->createGmtDateTime(
                $row->getData('online_regular_sale_price_end_date')
            )->format('U');

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
<div class="m2epro-field-tooltip m2epro-field-tooltip-price-info admin__field-tooltip">
    <a class="admin__field-tooltip-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content">
        <span style="color:gray;">
            <strong>From:</strong> {$fromDate}<br/>
            <strong>To:</strong> {$toDate}
        </span>
    </div>
</div>
HTML;

                $salePriceValue = $this->localeCurrency->getCurrency($currency)->toCurrency($salePrice);

                if ($isExport) {
                    return $salePriceValue;
                }

                if (
                    $currentTimestamp >= $startDateTimestamp &&
                    $currentTimestamp <= $endDateTimestamp &&
                    $salePrice < (float)$onlinePrice
                ) {
                    $resultHtml .= '<span style="color: grey; text-decoration: line-through;">' . $priceValue . '</span>' .
                        $repricingHtml;
                    $resultHtml .= '<br/>' . $intervalHtml . '&nbsp;' . $salePriceValue;
                } else {
                    $resultHtml .= $priceValue . $repricingHtml;
                    $resultHtml .= '<br/>' . $intervalHtml .
                        '<span style="color:gray;">' . '&nbsp;' . $salePriceValue . '</span>';
                }
            }
        }

        if (empty($resultHtml)) {
            if ($isExport) {
                return $priceValueExport;
            }

            $resultHtml = $priceValue . $repricingHtml;
        }

        if ((float)$onlineBusinessPrice > 0) {
            if ($isExport) {
                return $onlineBusinessPrice;
            }

            $businessPriceValue = '<strong>B2B:</strong> '
                . $this->localeCurrency->getCurrency($currency)->toCurrency($onlineBusinessPrice);

            $businessDiscounts = $row->getData('online_business_discounts');
            if (!empty($businessDiscounts) && $businessDiscounts = json_decode($businessDiscounts, true)) {
                $discountsHtml = '';

                foreach ($businessDiscounts as $qty => $price) {
                    $price = $this->localeCurrency->getCurrency($currency)->toCurrency($price);
                    $discountsHtml .= 'QTY >= ' . (int)$qty . ', price ' . $price . '<br />';
                }

                $businessPriceValue .= <<<HTML
<div style="position: relative; left: -35px;">
    {$this->getTooltipHtml($discountsHtml, false)}
</div>
HTML;
            }

            if (!empty($resultHtml)) {
                $businessPriceValue = '<br />' . $businessPriceValue;
            }

            $resultHtml .= $businessPriceValue;
        }

        return $resultHtml;
    }

    protected function getProductStatus($status)
    {
        switch ($status) {
            case \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN:
                return '<span style="color: gray;">' . __('Unknown') . '</span>';

            case \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED:
                return '<span style="color: gray;">' . __('Not Listed') . '</span>';

            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                return '<span style="color: green;">' . __('Active') . '</span>';

            case \Ess\M2ePro\Model\Listing\Product::STATUS_INACTIVE:
                return '<span style="color: red;">' . __('Inactive') . '</span>';

            case \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED:
                return '<span style="color: orange; font-weight: bold;">' .
                    __('Incomplete') . '</span>';
        }

        return '';
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/amazon_listing/allItems', ['_current' => true]);
    }

    public function getRowUrl($item)
    {
        return false;
    }

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
                AmazonListingAllItemsGrid.afterInitPage();
JS
            );

            return parent::_toHtml();
        }

        $this->jsUrl->addUrls([
            'runListProducts' => $this->getUrl('*/amazon_listing_allItems_actions/runListProducts'),
            'runRelistProducts' => $this->getUrl('*/amazon_listing_allItems_actions/runRelistProducts'),
            'runReviseProducts' => $this->getUrl('*/amazon_listing_allItems_actions/runReviseProducts'),
            'runStopProducts' => $this->getUrl('*/amazon_listing_allItems_actions/runStopProducts'),
            'runStopAndRemoveProducts' => $this->getUrl('*/amazon_listing_allItems_actions/runStopAndRemoveProducts'),
        ]);

        $this->jsUrl->add($this->getUrl('*/listing/getErrorsSummary'), 'getErrorsSummary');

        $this->jsTranslator->addTranslations([
            'task_completed_warning_message' => __('"%task_title%" task has completed with warnings.'),
            'task_completed_error_message' => __('"%task_title%" task has completed with errors.'),
            'task_completed_message' => __('Task completed. Please wait ...'),
            'task_completed_success_message' => __('"%task_title%" task has completed.'),
            'sending_data_message' => __('Sending %product_title% Product(s) data on Amazon.'),
            'please_select_the_products' => __('Please select the Products you want to perform the Action on.'),
        ]);

        $this->jsUrl->addUrls([
            'amazon_listing_product_repricing/getUpdatedPriceBySkus' => $this->getUrl(
                '*/amazon_listing_product_repricing/getUpdatedPriceBySkus'
            ),
        ]);

        $js = <<<JS
require([
    'M2ePro/Amazon/Listing/Product/Repricing/Price',
    'M2ePro/Amazon/Listing/AllItems/Grid'
], function() {
    window.AmazonListingProductRepricingPriceObj = new AmazonListingProductRepricingPrice();
    window.AmazonListingAllItemsGrid = new AmazonListingAllItemsGrid('{$this->getId()}');
    AmazonListingAllItemsGrid.afterInitPage();
});
JS;

        $this->js->add($js);

        return parent::_toHtml();
    }

    private function addMarketplaceAdvancedFilter(): void
    {
        $options = $this->advancedFilterAllItemsOptions->getMarketplaceOptions();
        if ($options->isEmpty()) {
            return;
        }

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
            string $filterValue
        ) {
            if (empty($filterValue)) {
                return;
            }

            $collection->addFieldToFilter('marketplace_id', ['eq' => (int)$filterValue]);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'marketplace',
            (string)__('Marketplace'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addAccountAdvancedFilter(): void
    {
        $options = $this->advancedFilterAllItemsOptions->getAccountOptions();
        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
            string $filterValue
        ): void {
            if (empty($filterValue)) {
                return;
            }

            $collection->addFieldToFilter('account_id', ['eq' => (int)$filterValue]);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'account',
            (string)__('Account'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addSellingPolicyAdvancedFilter(): void
    {
        $options = $this->advancedFilterAllItemsOptions->getSellingPolicyOptions();
        if ($options->isEmpty()) {
            return;
        }

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
            string $filterValue
        ): void {
            if (empty($filterValue)) {
                return;
            }

            $collection
                ->getSelect()
                ->where('al.template_selling_format_id = ?', (int)$filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'selling_policy',
            (string)__('Selling Policy'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addSynchronizationPolicyAdvancedFilter()
    {
        $options = $this->advancedFilterAllItemsOptions->getSynchronizationPolicyOptions();
        if ($options->isEmpty()) {
            return;
        }

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
            string $filterValue
        ): void {
            if (empty($filterValue)) {
                return;
            }

            $collection
                ->getSelect()
                ->where('al.template_synchronization_id = ?', (int)$filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'synchronization_policy',
            (string)__('Synchronization Policy'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addShippingPolicyAdvancedFilter()
    {
        $options = $this->advancedFilterAllItemsOptions->getShippingPolicyOptions();
        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
            string $filterValue
        ): void {
            if (empty($filterValue)) {
                return;
            }

            $collection
                ->getSelect()
                ->where('IF (
                    alp.template_shipping_id IS NOT NULL,
                    alp.template_shipping_id,
                    al.template_shipping_id
                ) = ?', (int)$filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'shipping_policy',
            (string)__('Shipping Policy'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addProductTypeAdvancedFilter()
    {
        $options = $this->advancedFilterAllItemsOptions->getProductTypeOptions();
        if ($options->isEmpty()) {
            return;
        }

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
            string $filterValue
        ): void {
            if (empty($filterValue)) {
                return;
            }

            $collection
                ->getSelect()
                ->where('alp.template_product_type_id = ?', (int)$filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'product_type',
            (string)__('Product Type'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addErrorsAdvancedFilter()
    {
        $options = $this->advancedFilterAllItemsOptions->getErrorOptions();
        if ($options->isEmpty()) {
            return;
        }

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
            string $filterValue
        ): void {
            if (empty($filterValue)) {
                return;
            }

            $subSelect = $this->getSubSelectForAmazonListingProductsWithTags((int)$filterValue);
            $collection->getSelect()->where('alp.listing_product_id IN (?)', $subSelect);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'errors_filter',
            (string)__('Amazon Error'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addTaxCodePolicyAdvancedFilter()
    {
        $options = $this->advancedFilterAllItemsOptions->getTaxCodeOptions();
        if ($options->isEmpty()) {
            return;
        }

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
            string $filterValue
        ): void {
            if (empty($filterValue)) {
                return;
            }

            $collection->getSelect()->where('alp.template_product_tax_code_id = ?', $filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'tax_code',
            (string)__('Tax Code Policy'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addMagentoProductTypeAdvancedFilter(): void
    {
        $options = $this->advancedFilterAllItemsOptions->getMagentoProductTypeOptions();

        if ($options->isEmpty()) {
            return;
        }

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
            string $filterValue
        ): void {
            if (empty($filterValue)) {
                return;
            }

            $collection->getSelect()->where('e.type_id = ?', $filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'magento_product_type',
            (string)__('Magento Product Type'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }
}
