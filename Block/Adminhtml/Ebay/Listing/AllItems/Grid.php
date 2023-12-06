<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AllItems;

use Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty as OnlineQty;
use Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\Option as ProductVariationOption;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product */
    private $ebayListingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Marketplace */
    private $ebayMarketplaceResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Item */
    private $ebayItemResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing */
    private $listingResource;
    /** @var ProductVariationOption\CollectionFactory */
    private $productVarOptionCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation */
    private $listingProductVariationResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation */
    private $tagRelationResource;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory */
    private $magentoProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;
    /** @var \Ess\M2ePro\Helper\View\Ebay */
    private $ebayViewHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Url */
    private $urlHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing */
    private $ebayListingResource;
    /** @var \Ess\M2ePro\Model\Ebay\AdvancedFilter\AllItemsOptions */
    private $advancedFilterAllItemsOptions;
    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\FilterFactory */
    private $advancedFilterFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category */
    private $ebayCategoryResource;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\FilterFactory $advancedFilterFactory,
        \Ess\M2ePro\Model\Ebay\AdvancedFilter\AllItemsOptions $advancedFilterAllItemsOptions,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Category $ebayCategoryResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing $ebayListingResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Marketplace $ebayMarketplaceResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Item $ebayItemResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation $listingProductVariationResource,
        \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation $tagRelationResource,
        ProductVariationOption\CollectionFactory $productVarOptionCollectionFactory,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Url $urlHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);

        $this->listingProductResource = $listingProductResource;
        $this->ebayListingProductResource = $ebayListingProductResource;
        $this->ebayMarketplaceResource = $ebayMarketplaceResource;
        $this->ebayItemResource = $ebayItemResource;
        $this->listingResource = $listingResource;
        $this->listingProductVariationResource = $listingProductVariationResource;
        $this->productVarOptionCollectionFactory = $productVarOptionCollectionFactory;
        $this->databaseHelper = $databaseHelper;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->ebayFactory = $ebayFactory;
        $this->ebayViewHelper = $ebayViewHelper;
        $this->dataHelper = $dataHelper;
        $this->urlHelper = $urlHelper;
        $this->ebayListingResource = $ebayListingResource;
        $this->tagRelationResource = $tagRelationResource;
        $this->advancedFilterAllItemsOptions = $advancedFilterAllItemsOptions;
        $this->advancedFilterFactory = $advancedFilterFactory;
        $this->ebayCategoryResource = $ebayCategoryResource;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingAllItemsGrid');

        $this->setDefaultSort(false);
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing/allItems', ['_current' => true]);
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
            'product_id=entity_id',
            [
                'id' => 'id',
                'status' => 'status',
                'component_mode' => 'component_mode',
                'listing_id' => 'listing_id',
                'additional_data' => 'additional_data',
            ]
        );

        $collection->joinTable(
            ['elp' => $this->ebayListingProductResource->getMainTable()],
            'listing_product_id=id',
            [
                'listing_product_id' => 'listing_product_id',
                'ebay_item_id' => 'ebay_item_id',
                'online_title' => 'online_title',
                'online_sku' => 'online_sku',
                'online_qty' => new \Zend_Db_Expr('(elp.online_qty - elp.online_qty_sold)'),
                'online_qty_sold' => 'online_qty_sold',
                'online_bids' => 'online_bids',
                'online_start_price' => 'online_start_price',
                'online_current_price' => 'online_current_price',
                'online_reserve_price' => 'online_reserve_price',
                'online_buyitnow_price' => 'online_buyitnow_price',

                'is_duplicate' => 'is_duplicate',
            ]
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
            ['el' => $this->ebayListingResource->getMainTable()],
            'l.id = el.listing_id',
            null
        );
        $collection->joinTable(
            ['em' => $this->ebayMarketplaceResource->getMainTable()],
            'marketplace_id=marketplace_id',
            [
                'currency' => 'currency',
            ]
        );
        $collection->joinTable(
            ['ei' => $this->ebayItemResource->getMainTable()],
            'id=ebay_item_id',
            [
                'item_id' => 'item_id',
            ],
            null,
            'left'
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addExportType('*/*/exportCsvAllItemsGrid', __('CSV'));

        $this->addColumn('product_id', [
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

        $this->addColumn('item_id', [
            'header' => __('Item ID'),
            'align' => 'left',
            'width' => '100px',
            'type' => 'text',
            'index' => 'item_id',
            'filter_index' => 'item_id',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\ItemId::class,
            'filter_condition_callback' => [$this, 'callbackFilterItemId'],
        ]);

        $this->addColumn('online_qty', [
            'header' => __('Available QTY'),
            'align' => 'right',
            'width' => '50px',
            'type' => 'number',
            'index' => 'online_qty',
            'filter_index' => 'online_qty',
            'renderer' => OnlineQty::class,
            'render_online_qty' => OnlineQty::ONLINE_AVAILABLE_QTY,
            'filter_condition_callback' => [$this, 'callbackFilterOnlineQty'],
        ]);

        $this->addColumn('online_qty_sold', [
            'header' => __('Sold QTY'),
            'align' => 'right',
            'width' => '50px',
            'type' => 'number',
            'index' => 'online_qty_sold',
            'filter_index' => 'online_qty_sold',
            'renderer' => OnlineQty::class,
        ]);

        $this->addColumn('price', [
            'header' => __('Price'),
            'align' => 'right',
            'width' => '50px',
            'type' => 'number',
            'index' => 'online_current_price',
            'filter_index' => 'online_current_price',
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\CurrentPrice::class,
            'filter_condition_callback' => [$this, 'callbackFilterPrice'],
        ]);

        $statusColumn = [
            'header' => __('Status'),
            'width' => '100px',
            'index' => 'status',
            'filter_index' => 'status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => __('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => __('Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN => __('Listed (Hidden)'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD => __('Sold'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => __('Stopped'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED => __('Finished'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => __('Pending'),
            ],
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Status::class,
            'filter_condition_callback' => [$this, 'callbackFilterStatus'],
        ];

        if ($this->ebayViewHelper->isDuplicatesFilterShouldBeShown()) {
            $statusColumn['filter'] = \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\Status::class;
        }

        $this->addColumn('status', $statusColumn);

        $this->addColumn('goto_listing_item', [
            'header' => __('Manage'),
            'align' => 'center',
            'width' => '50px',
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
        $this->addAccountsAdvancedFilter();

        $this->addSellingPolicyAdvancedFilter();
        $this->addSynchronizationPolicyAdvancedFilter();
        $this->addDescriptionPolicyAdvancedFilter();
        $this->addShippingPolicyAdvancedFilter();
        $this->addReturnPolicyAdvancedFilter();
        $this->addCategoryAdvancedFilter();

        $this->addErrorAdvancedFilter();

        parent::_prepareAdvancedFilters();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        $this->getMassactionBlock()->addItem('list', [
            'label' => __('List Item(s) on eBay'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('revise', [
            'label' => __('Revise Item(s) on eBay'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('relist', [
            'label' => __('Relist Item(s) on eBay'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('stop', [
            'label' => __('Stop Item(s) on eBay'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('stopAndRemove', [
            'label' => __('Stop on eBay / Remove From Listing'),
            'url' => '',
        ], 'actions');

        return parent::_prepareMassaction();
    }

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
                EbayListingAllItemsGrid.afterInitPage();
JS
            );

            return parent::_toHtml();
        }

        $this->jsUrl->addUrls([
            'runListProducts' => $this->getUrl('*/ebay_listing_allItems_actions/runListProducts'),
            'runRelistProducts' => $this->getUrl('*/ebay_listing_allItems_actions/runRelistProducts'),
            'runReviseProducts' => $this->getUrl('*/ebay_listing_allItems_actions/runReviseProducts'),
            'runStopProducts' => $this->getUrl('*/ebay_listing_allItems_actions/runStopProducts'),
            'runStopAndRemoveProducts' => $this->getUrl('*/ebay_listing_allItems_actions/runStopAndRemoveProducts'),
        ]);

        $this->jsUrl->add($this->getUrl('*/listing/getErrorsSummary'), 'getErrorsSummary');

        $this->jsTranslator->addTranslations([
            'task_completed_warning_message' => __('"%task_title%" task has completed with warnings.'),
            'task_completed_error_message' => __('"%task_title%" task has completed with errors.'),
            'task_completed_message' => __('Task completed. Please wait ...'),
            'task_completed_success_message' => __('"%task_title%" task has completed.'),
            'sending_data_message' => __('Sending %product_title% Product(s) data on eBay.'),
            'listing_selected_items_message' => __('Listing Selected Items On eBay'),
            'revising_selected_items_message' => __('Revising Selected Items On eBay'),
            'relisting_selected_items_message' => __('Relisting Selected Items On eBay'),
            'stopping_selected_items_message' => __('Stopping Selected Items On eBay'),
            'stopping_and_removing_selected_items_message' => __(
                'Stopping On eBay And Removing From Listing Selected Items'
            ),
            'please_select_the_products' => __('Please select the Products you want to perform the Action on.'),
        ]);

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'M2ePro/Ebay/Listing/AllItems/Grid'
    ], function(){
        window.EbayListingAllItemsGrid = new EbayListingAllItemsGrid('{$this->getId()}');
        EbayListingAllItemsGrid.afterInitPage();
    });
JS
        );

        return parent::_toHtml();
    }

    public function callbackColumnProductTitle(
        string $value,
        \Magento\Catalog\Model\Product $row,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column,
        bool $isExport
    ): string {
        if ($isExport) {
            return $row->getData('sku');
        }

        $title = $row->getName();
        $onlineTitle = $row->getData('online_title');

        if (!empty($onlineTitle)) {
            $title = $onlineTitle;
        }

        $value = '<div style="margin-bottom: 5px;">' . $this->dataHelper->escapeHtml($title) . '</div>';
        $additionalHtml = $this->getColumnProductTitleAdditionalHtml($row);

        return $value . $additionalHtml;
    }

    protected function getColumnProductTitleAdditionalHtml(\Magento\Catalog\Model\Product $row): string
    {
        $listingWord = __('Listing');
        $listingUrl = $this->getUrl('*/ebay_listing/view', ['id' => $row->getData('listing_id')]);

        $listingTitle = $this->dataHelper->escapeHtml($row->getData('listing_title'));
        $listingTitle = $this->filterManager->truncate($listingTitle, ['length' => 50]);

        $html = <<<HTML
<strong> {$listingWord}:</strong>&nbsp;
<a href="{$listingUrl}" target="_blank">{$listingTitle}</a>
HTML;

        $account = $this->ebayFactory->getCachedObjectLoaded('Account', $row->getData('account_id'));
        $marketplace = $this->ebayFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

        $html .= '<br/><strong>' . __('Account') . ':</strong>'
            . '&nbsp;' . $account->getTitle() . '<br/>'
            . '<strong>' . __('Marketplace') . ':</strong>'
            . '&nbsp;' . $marketplace->getTitle();

        $sku = $row->getData('sku');
        $onlineSku = $row->getData('online_sku');

        !empty($onlineSku) && $sku = $onlineSku;
        $sku = $this->dataHelper->escapeHtml($sku);

        $skuWord = __('SKU');
        $html .= <<<HTML
<br/><strong>{$skuWord}:</strong>&nbsp;{$sku}
HTML;

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $row->getData('listing_product_id'));
        if ($listingProduct->getChildObject()->isVariationsReady()) {
            $additionalData = \Ess\M2ePro\Helper\Json::decode($row->getData('additional_data'));
            $productAttributes = array_keys($additionalData['variations_sets']);
            $productAttributes = implode(', ', $productAttributes);

            $html .= <<<HTML
<div style="font-size: 11px; font-weight: bold; color: grey; margin: 7px 0 0 7px">
    {$productAttributes}
</div>
HTML;
        }

        return $html;
    }

    public function callbackColumnActions(
        string $value,
        \Magento\Catalog\Model\Product $row,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column,
        bool $isExport
    ): string {
        $productId = (int)$row->getEntityId();

        $urlData = [
            'back' => $this->urlHelper->makeBackUrlParam('*/ebay_listing/allItems'),
            'id' => $row->getData('listing_id'),
            'view_mode' => \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Switcher::VIEW_MODE_EBAY,
            'filter' => base64_encode("product_id[from]={$productId}&product_id[to]={$productId}"),
        ];

        $searchedChildHtml = '';
        if ($this->wasFoundByChild($row)) {
            $urlData['child_variation_ids'] = $this->getChildVariationIds($row);

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

        $manageUrl = $this->getUrl('*/ebay_listing/view/', $urlData);
        $html = <<<HTML
<div style="float:right; margin:5px 15px 0 0;">
    <a title="{$this->__('Go to Listing')}" target="_blank" href="{$manageUrl}">
    <img src="{$this->getViewFileUrl('Ess_M2ePro::images/goto_listing.png')}" /></a>
</div>
HTML;

        return $searchedChildHtml . $html;
    }

    private function wasFoundByChild(\Magento\Catalog\Model\Product $row): bool
    {
        foreach (['product_id', 'product_sku'] as $item) {
            $searchedByChild = $row->getData("{$item}_searched_by_child");
            if (!empty($searchedByChild)) {
                return true;
            }
        }

        return false;
    }

    private function getChildVariationIds(\Magento\Catalog\Model\Product $row): string
    {
        $ids = [];

        foreach (['product_id', 'product_sku'] as $item) {
            $itemIds = $row->getData("{$item}_child_variation_ids");
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

    protected function callbackFilterProductId(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
    ): void {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $childCollection = $this->getMagentoChildProductsCollection();
        $childCollection->addFieldToFilter('product_id', $cond);

        $collection->getSelect()->joinLeft(
            ['product_id_subQuery' => $childCollection->getSelect()],
            'product_id_subQuery.listing_product_id=lp.id',
            [
                'product_id_child_variation_ids' => 'child_variation_ids',
                'product_id_searched_by_child' => 'searched_by_child',
            ]
        );

        $collection->addFieldToFilter([
            ['attribute' => 'entity_id', $cond],
            ['attribute' => 'product_id_subQuery.searched_by_child', 'eq' => '1', 'raw' => true],
        ]);
    }

    protected function callbackFilterTitle(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
    ): void {
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
            'product_sku_subQuery.listing_product_id=lp.id',
            [
                'product_sku_child_variation_ids' => 'child_variation_ids',
                'product_sku_searched_by_child' => 'searched_by_child',
            ]
        );

        $collection->addFieldToFilter([
            ['attribute' => 'sku', 'like' => '%' . $value . '%'],
            ['attribute' => 'online_sku', 'like' => '%' . $value . '%'],
            ['attribute' => 'name', 'like' => '%' . $value . '%'],
            ['attribute' => 'online_title', 'like' => '%' . $value . '%'],
            ['attribute' => 'listing_title', 'like' => '%' . $value . '%'],
            ['attribute' => 'product_sku_subQuery.searched_by_child', 'eq' => '1', 'raw' => true],
        ]);
    }

    protected function callbackFilterOnlineQty(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
    ): void {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $where = '';
        $onlineQty = 'elp.online_qty - elp.online_qty_sold';

        if (isset($cond['from']) || isset($cond['to'])) {
            if (isset($cond['from']) && $cond['from'] != '') {
                $value = $collection->getConnection()->quote($cond['from']);
                $where .= "{$onlineQty} >= {$value}";
            }

            if (isset($cond['to']) && $cond['to'] != '') {
                if (isset($cond['from']) && $cond['from'] != '') {
                    $where .= ' AND ';
                }
                $value = $collection->getConnection()->quote($cond['to']);
                $where .= "{$onlineQty} <= {$value}";
            }
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterPrice(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
    ): void {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $collection->addFieldToFilter('online_current_price', $cond);
    }

    protected function callbackFilterStatus(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
    ): void {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $index = $column->getIndex();

        if (is_array($value) && isset($value['value'])) {
            $collection->addFieldToFilter($index, (int)$value['value']);
        } elseif (!is_array($value) && $value !== null) {
            $collection->addFieldToFilter($index, (int)$value);
        }

        if (isset($value['is_duplicate'])) {
            $collection->addFieldToFilter('is_duplicate', 1);
        }
    }

    protected function callbackFilterItemId(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
    ): void {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $collection->addFieldToFilter('item_id', $cond);
    }

    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if ($collection) {
            $columnIndex = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();

            if ($columnIndex == 'online_qty') {
                // fix for wrong fields wrapping with "`" when statement in ()
                $onlineQty = 'IF(
                    1=1,
                    elp.online_qty - elp.online_qty_sold,
                    NULL
                )';
                $collection->getSelect()->order(
                    $onlineQty . ' ' . strtoupper($column->getDir())
                );
            } else {
                $collection->setOrder($columnIndex, strtoupper($column->getDir()));
            }
        }

        return $this;
    }

    /**
     * @return ProductVariationOption\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getMagentoChildProductsCollection(): ProductVariationOption\Collection
    {
        $collection = $this->productVarOptionCollectionFactory->create();
        $collection->addFieldToSelect('listing_product_variation_id');
        $collection->addFieldToFilter('main_table.component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK);
        $collection->getSelect()->joinLeft(
            [
                'lpv' => $this->listingProductVariationResource->getMainTable(),
            ],
            'lpv.id=main_table.listing_product_variation_id',
            ['listing_product_id']
        );

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'child_variation_ids' => new \Zend_Db_Expr('GROUP_CONCAT(lpv.id)'),
                'listing_product_id' => 'lpv.listing_product_id',
                'searched_by_child' => new \Zend_Db_Expr(1),
            ]
        );

        $collection->getSelect()->group("lpv.listing_product_id");

        return $collection;
    }

    /**
     * @param $item
     *
     * @return false
     */
    public function getRowUrl($item)
    {
        return false;
    }

    private function addMarketplaceAdvancedFilter()
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
            __('Marketplace'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addAccountsAdvancedFilter()
    {
        $options = $this->advancedFilterAllItemsOptions->getAccountOptions();
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

            $collection->addFieldToFilter('account_id', ['eq' => (int)$filterValue]);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'account',
            __('Account'),
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
                ->where('IF (
                    elp.template_selling_format_id IS NOT NULL,
                    elp.template_selling_format_id,
                    el.template_selling_format_id
                ) = ?', (int)$filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'selling_policy',
            __('Selling Policy'),
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
                ->where('IF (
                    elp.template_synchronization_id IS NOT NULL,
                    elp.template_synchronization_id,
                    el.template_synchronization_id
                ) = ?', (int)$filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'synchronization_policy',
            __('Synchronization Policy'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addDescriptionPolicyAdvancedFilter()
    {
        $options = $this->advancedFilterAllItemsOptions->getDescriptionPolicyOptions();
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
                ->where('IF (
                    elp.template_description_id IS NOT NULL,
                    elp.template_description_id,
                    el.template_description_id
                ) = ?', (int)$filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'description_policy',
            __('Description Policy'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addShippingPolicyAdvancedFilter()
    {
        $options = $this->advancedFilterAllItemsOptions->getShippingPolicyOptions();
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
                ->where('IF (
                    elp.template_shipping_id IS NOT NULL,
                    elp.template_shipping_id,
                    el.template_shipping_id
                ) = ?', (int)$filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'shipping_policy',
            __('Shipping Policy'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addReturnPolicyAdvancedFilter()
    {
        $options = $this->advancedFilterAllItemsOptions->getReturnPolicyOptions();
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
                ->where('IF (
                    elp.template_return_policy_id IS NOT NULL,
                    elp.template_return_policy_id,
                    el.template_return_policy_id
                ) = ?', (int)$filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'return_policy',
            __('Return Policy'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addCategoryAdvancedFilter()
    {
        $options = $this->advancedFilterAllItemsOptions->getCategoryOptions();
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

            $collection->getSelect()->joinLeft(
                ['category' => $this->ebayCategoryResource->getMainTable()],
                'elp.template_category_id = category.id',
                []
            );

            $collection->getSelect()->where('category.category_id = ?', (int)$filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'category',
            __('Category'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addErrorAdvancedFilter()
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

            $collection->getSelect()->joinInner(
                ['tag_relation' => $this->tagRelationResource->getMainTable()],
                'tag_relation.listing_product_id = lp.id',
                []
            );

            $collection->getSelect()->where('tag_relation.tag_id = ?', $filterValue);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'errors_filter',
            __('eBay Error'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }
}
