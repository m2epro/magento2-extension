<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SearchAsin;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    public const SEARCH_SETTINGS_STATUS_NONE = 'none';
    public const SEARCH_SETTINGS_STATUS_COMPLETED = 'completed';

    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    private \Ess\M2ePro\Model\Amazon\Listing\ProductIdentifiersConfig $productIdentifiersConfig;
    private ?\Ess\M2ePro\Model\Listing $listing = null;
    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory */
    protected $magentoProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    protected $amazonFactory;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;
    protected $lockedDataCache = [];
    private bool $isExistsGeneralIdAttribute = false;
    private bool $isExistsWorldwideAttribute = false;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Model\Amazon\Listing\ProductIdentifiersConfig $productIdentifiersConfig,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        $this->productIdentifiersConfig = $productIdentifiersConfig;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->amazonFactory = $amazonFactory;
        $this->dataHelper = $dataHelper;
        $this->amazonHelper = $amazonHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $this->getRequest()->getParam('id'));
        $this->listing = $listing;

        /** @var \Ess\M2ePro\Model\Amazon\Listing $amazonListing */
        $amazonListing = $listing->getChildObject();
        $this->isExistsGeneralIdAttribute = $this->productIdentifiersConfig->isExistsGeneralIdAttribute($amazonListing);
        $this->isExistsWorldwideAttribute = $this->productIdentifiersConfig->isExistsWorldwideAttribute($amazonListing);

        // Initialization block
        // ---------------------------------------
        $this->setId('searchAsinForListingProductsGrid' . $this->listing['id']);
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $listingProductsIds = $this->listing->getSetting('additional_data', 'adding_listing_products_ids');

        // Get collection
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection
            ->setListing($this->listing)
            ->setStoreId($this->listing['store_id'])
            ->setListingProductModeOn()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku');

        $collection->joinStockItem();

        // ---------------------------------------
        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            [
                'id' => 'id',
                'component_mode' => 'component_mode',
                'amazon_status' => 'status',
                'additional_data' => 'additional_data',
            ],
            '{{table}}.listing_id=' . (int)$this->listing['id']
        );

        $alpTable = $this->activeRecordFactory->getObject('Amazon_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['alp' => $alpTable],
            'listing_product_id=id',
            [
                'general_id' => 'general_id',
                'general_id_search_info' => 'general_id_search_info',
                'search_settings_status' => 'search_settings_status',
                'search_settings_data' => 'search_settings_data',
                'variation_child_statuses' => 'variation_child_statuses',
                'amazon_sku' => 'sku',
                'online_qty' => 'online_qty',
                'online_regular_price' => 'online_regular_price',
                'online_regular_sale_price' => 'online_regular_sale_price',
                'is_afn_channel' => 'is_afn_channel',
                'is_general_id_owner' => 'is_general_id_owner',
                'is_variation_parent' => 'is_variation_parent',
            ],
            '{{table}}.variation_parent_id is NULL'
        );

        $collection->getSelect()->where('lp.id IN (?)', $listingProductsIds);

        // ---------------------------------------

        $collection->getSelect()->group('lp.id');

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header' => __('Product ID'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'entity_id',
            'filter_index' => 'entity_id',
            'store_id' => $this->listing->getStoreId(),
            'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class,
        ]);

        $this->addColumn('name', [
            'header' => __('Product Title / Product SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'name',
            'filter_index' => 'name',
            'escape' => false,
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        $this->addColumn('general_id', [
            'header' => __('ASIN / ISBN'),
            'align' => 'left',
            'width' => '140px',
            'type' => 'text',
            'index' => 'general_id',
            'filter_index' => 'general_id',
            'frame_callback' => [$this, 'callbackColumnGeneralId'],
        ]);

        if (
            $this->isExistsGeneralIdAttribute
            || $this->isExistsWorldwideAttribute
        ) {
            $this->addColumn('settings', [
                'header' => __('Search Values'),
                'align' => 'left',
                'width' => '240px',
                'filter' => false,
                'sortable' => false,
                'type' => 'text',
                'index' => 'id',
                'frame_callback' => [$this, 'callbackColumnSettings'],
            ]);
        }

        $this->addColumn('status', [
            'header' => __('Status'),
            'width' => '200px',
            'index' => 'search_settings_status',
            'filter_index' => 'search_settings_status',
            'sortable' => false,
            'type' => 'options',
            'options' => [
                self::SEARCH_SETTINGS_STATUS_NONE => __('None'),
                \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS =>
                    __('In Progress'),
                \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_IDENTIFIER_INVALID =>
                    __('Product ID not found'),
                \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_NOT_FOUND =>
                    __('Not Found'),
                \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_ACTION_REQUIRED =>
                    __('Action Required'),
                self::SEARCH_SETTINGS_STATUS_COMPLETED => __('Completed'),
            ],
            'frame_callback' => [$this, 'callbackColumnStatus'],
            'filter_condition_callback' => [$this, 'callbackFilterStatus'],
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // ---------------------------------------
        $this->getMassactionBlock()->addItem('assignGeneralId', [
            'label' => __('Search ASIN/ISBN automatically'),
            'url' => '',
        ]);

        $this->getMassactionBlock()->addItem('unassignGeneralId', [
            'label' => __('Reset ASIN/ISBN information'),
            'url' => '',
        ]);

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    public function callbackColumnProductTitle($productTitle, $row, $column, $isExport)
    {
        $productTitle = $this->dataHelper->escapeHtml($productTitle);

        $value = '<span>' . $productTitle . '</span>';

        $tempSku = $row->getData('sku');
        $tempSku === null
        && $tempSku = $this->modelFactory->getObject('Magento\Product')
                                         ->setProductId($row->getData('entity_id'))
                                         ->getSku();

        $value .= '<br/><strong>' . __('SKU') .
            ':</strong> ' . $this->dataHelper->escapeHtml($tempSku) . '<br/>';

        $listingProductId = (int)$row->getData('id');
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $listingProductId);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $listingProduct->getChildObject()->getVariationManager();

        if (!$variationManager->isRelationParentType()) {
            return $value;
        }

        $productAttributes = (array)$variationManager->getTypeModel()->getProductAttributes();

        $value .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin-left: 7px"><br/>';
        $value .= implode(', ', $productAttributes);
        $value .= '</div>';

        return $value;
    }

    public function callbackColumnGeneralId($generalId, $row, $column, $isExport)
    {
        if (empty($generalId)) {
            return $this->getGeneralIdColumnValueEmptyGeneralId($row);
        }

        return $this->getGeneralIdColumnValueNotEmptyGeneralId($row);
    }

    public function callbackColumnSettings($id, $row, $column, $isExport)
    {
        $result = '';

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $listingProduct */
        $listingProduct = $this->amazonFactory->getObjectLoaded('Listing\Product', $id)->getChildObject();
        $identifiers = $listingProduct->getIdentifiers();

        $asinValue = __('Not set');
        if ($generalId = $identifiers->getGeneralId()) {
            $asinValue = $generalId->hasResolvedType()
                ? $generalId->getIdentifier()
                : __('Inappropriate value');
        }
        $result .= sprintf('<b>%s</b>: %s<br/>', __('ASIN/ISBN'), $asinValue);

        $worldwideIdValue = __('Not set');
        if ($worldwideId = $identifiers->getWorldwideId()) {
            $worldwideIdValue = $worldwideId->hasResolvedType()
                ? $worldwideId->getIdentifier()
                : __('Inappropriate value');
        }

        $result .= sprintf('<b>%s</b>: %s<br/>', __('UPC/EAN'), $worldwideIdValue);

        return $result;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $generalId = $row->getData('general_id');
        $searchSettingsStatus = $row->getData('search_settings_status');
        $style = 'display: inline-block; vertical-align: middle; line-height: 30px;';

        if (empty($generalId) && empty($searchSettingsStatus)) {
            $msg = __('None');
            $tip = __('The Search of Product was not performed yet');

            return <<<HTML
<span style="color: gray; {$style}">{$msg}</span>&nbsp;
{$this->getTooltipHtml($tip)}
HTML;
        }

        switch ($searchSettingsStatus) {
            case \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS:
                $searchData = \Ess\M2ePro\Helper\Json::decode($row->getData('search_settings_data'));

                $msg = __('In Progress');
                $tip = $this->__(
                    'The Search is being performed now by %type% "%value%"',
                    $this->prepareSearchType($searchData['type']),
                    $searchData['value']
                );

                return <<<HTML
<div class="status-wrap">
<span style="color: orange; {$style}">{$msg}
{$this->getTooltipHtml($tip)}</span></div>
HTML;
            case \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_IDENTIFIER_INVALID:
                $msg = __('Invalid Product ID');
                $tip = __('Product ID is missing or has invalid format');

                return <<<HTML
<div class="status-wrap">
<span style="color: red; {$style}">{$msg}
{$this->getTooltipHtml($tip)}</span>
</div>
HTML;

            case \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_NOT_FOUND:
                $msg = __('Product was not found');
                $tip = __('There are no Products found on Amazon after the Automatic Search was performed.');

                return <<<HTML
<div class="status-wrap">
<span style="color: red; {$style}">{$msg}
{$this->getTooltipHtml($tip)}</span>
</div>
HTML;
            case \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_ACTION_REQUIRED:
                $searchData = \Ess\M2ePro\Helper\Json::decode($row->getData('search_settings_data'));

                $lpId = $row->getData('id');

                $productTitle = $row->getData('name');
                if (strlen($productTitle) > 60) {
                    $productTitle = substr($productTitle, 0, 60) . '...';
                }
                $productTitle = $this->dataHelper->escapeHtml($productTitle);

                $productTitle = __(
                    'Search ASIN/ISBN For &quot;%1&quot;',
                    $productTitle
                );
                $productTitle = $this->dataHelper->escapeJs($productTitle);

                $linkTxt = __('choose one of the Results');

                $linkHtml = <<<HTML
<a href="javascript:void(0)"
    onclick="ListingGridObj.productSearchHandler.openPopUp(1,'{$productTitle}',{$lpId})">{$linkTxt}</a>
HTML;

                $msg = __('Action Required');
                $tip = $this->__(
                    'Please %link% that were found by %type% "%value%"',
                    $linkHtml,
                    $this->prepareSearchType($searchData['type']),
                    $searchData['value']
                );

                return <<<HTML
<div class="status-wrap">
<span style="color: orange; {$style}">{$msg}
{$this->getTooltipHtml($tip)}</span>
</div>
HTML;
        }

        $searchInfo = \Ess\M2ePro\Helper\Json::decode($row->getData('general_id_search_info'));

        $msg = __('Completed');
        $tip = __(
            'Product was found by %1 "%2"',
            $this->prepareSearchType($searchInfo['type']),
            $searchInfo['value']
        );

        return <<<HTML
<div class="status-wrap">
<span style="color: green; {$style}">{$msg}
{$this->getTooltipHtml($tip)}</span>
</div>
HTML;
    }

    private function prepareSearchType($searchType)
    {
        if ($searchType == 'string') {
            return 'query';
        }

        return strtoupper($searchType);
    }

    private function getGeneralIdColumnValueEmptyGeneralId($row)
    {
        // ---------------------------------------
        $lpId = $row->getData('id');

        $productTitle = $row->getData('name');
        if (strlen($productTitle) > 60) {
            $productTitle = substr($productTitle, 0, 60) . '...';
        }
        $productTitle = $this->dataHelper->escapeHtml($productTitle);

        $productTitle = __('Search ASIN/ISBN For &quot;%1&quot;', $productTitle);
        $productTitle = $this->dataHelper->escapeJs($productTitle);
        // ---------------------------------------

        // ---------------------------------------

        $searchSettingsStatus = $row->getData('search_settings_status');

        // ---------------------------------------
        if ($searchSettingsStatus == \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_IN_PROGRESS) {
            $tip = __('Automatic ASIN/ISBN Search in Progress.');
            $iconSrc = $this->getViewFileUrl('Ess_M2ePro::images/search_statuses/processing.gif');

            return <<<HTML
&nbsp;
<a href="javascript: void(0);" title="{$tip}">
    <img src="{$iconSrc}" alt="">
</a>
HTML;
        }
        // ---------------------------------------

        // ---------------------------------------
        if ($searchSettingsStatus == \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_ACTION_REQUIRED) {
            $linkTxt = __('Choose ASIN/ISBN');

            return <<<HTML
<a href="javascript:;" title="{$linkTxt}"
   onclick="ListingGridObj.productSearchHandler.openPopUp(1,'{$productTitle}',{$lpId})">{$linkTxt}</a>
HTML;
        }
        // ---------------------------------------

        $na = __('N/A');
        $tip = __('Search for ASIN/ISBN');

        return <<<HTML
{$na} &nbsp;
<a href="javascript:;" title="{$tip}" class="amazon-listing-view-icon amazon-listing-view-generalId-search"
   onclick="ListingGridObj.productSearchHandler.showSearchManualPrompt('{$productTitle}',{$lpId});">
</a>
HTML;
    }

    private function getGeneralIdColumnValueNotEmptyGeneralId($row)
    {
        $generalId = $row->getData('general_id');
        $marketplaceId = $this->listing->getMarketplaceId();

        $url = $this->amazonHelper->getItemUrl(
            $generalId,
            $marketplaceId
        );

        $generalIdSearchInfo = $row->getData('general_id_search_info');

        if (!empty($generalIdSearchInfo)) {
            $generalIdSearchInfo = \Ess\M2ePro\Helper\Json::decode($generalIdSearchInfo);
        }

        if (!empty($generalIdSearchInfo['is_set_automatic'])) {
            $tip = __('ASIN/ISBN was found automatically');

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
            return $text;
        }

        $listingProductId = (int)$row->getData('id');

        $tip = __('Unassign ASIN/ISBN');

        $text .= <<<HTML
&nbsp;
<a href="javascript:;"
    class="amazon-listing-view-icon amazon-listing-view-generalId-remove"
    onclick="ListingGridObj.productSearchHandler.showUnmapFromGeneralIdPrompt({$listingProductId});"
    title="{$tip}">
</a>
HTML;

        return $text;
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

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        if ($value == self::SEARCH_SETTINGS_STATUS_NONE) {
            $collection->addFieldToFilter('general_id', ['null' => null]);
            $collection->addFieldToFilter('search_settings_status', ['null' => null]);

            return;
        }

        if ($value == self::SEARCH_SETTINGS_STATUS_COMPLETED) {
            $collection->addFieldToFilter(
                [
                    ['attribute' => 'general_id', 'notnull' => null],
                ]
            );

            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute' => 'search_settings_status', 'eq' => $value],
            ]
        );
    }

    protected function getLockedData($row)
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

    public function getRowUrl($item)
    {
        return false;
    }

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    ListingGridObj.afterInitPage();
JS
            );

            return parent::_toHtml();
        }

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Amazon/Listing/Product/Add/SearchAsin/Grid'
    ],function() {

        ListingGridObj = new AmazonListingProductAddSearchAsinGrid(
            '{$this->getId()}',
            {$this->listing->getId()}
        );

        ListingGridObj.actionHandler.setProgressBar('search_asin_progress_bar');
        ListingGridObj.actionHandler.setGridWrapper('search_asin_content_container');
        ListingGridObj.afterInitPage();
    });
JS
        );

        if (
            !$this->isExistsGeneralIdAttribute
            && !$this->isExistsWorldwideAttribute
        ) {
            $warningNotification = $this->__(
                "To have your products assigned to the existing ASIN/ISBN in the Amazon catalog, please configure"
                . " Product Identifiers settings in Amazon > Configuration > Settings > Main"
                . " or use <a href='%url%' target='_blank' class='external-link'>New ASIN creation</a> option.",
                $this->supportHelper->getDocumentationArticleUrl(
                    'help/m2/amazon-integration/m2e-pro-listings/asin-isbn-management'
                )
            );

            $this->js->add(
                <<<JS
require([
    'M2ePro/Plugin/Messages'
], function(MessageObj) {
    MessageObj.addWarning("$warningNotification")
});
JS
            );
        } else {
            $autoSearchSetting = $this->listing->getSetting('additional_data', 'auto_search_was_performed');

            if (!$autoSearchSetting) {
                $this->listing->setSetting('additional_data', 'auto_search_was_performed', 1);
                $this->listing->save();

                $this->js->add(
                    <<<JS
require([
    'M2ePro/Amazon/Listing/Product/Add/SearchAsin/Grid'
],function() {
    ListingGridObj.getGridMassActionObj().selectAll();
    ListingGridObj.productSearchHandler.searchGeneralIdAuto(ListingGridObj.getSelectedProductsString());
});
JS
                );
            }
        }

        return '<div id="search_asin_progress_bar"></div>' .
            '<div id="search_asin_content_container">' .
            parent::_toHtml() .
            '</div>';
    }
}
