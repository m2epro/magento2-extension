<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay;

use Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty as OnlineQty;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Ebay\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\View\Grid
{
    protected $magentoProductCollectionFactory;
    protected $ebayFactory;
    protected $localeCurrency;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->ebayFactory = $ebayFactory;
        $this->localeCurrency = $localeCurrency;
        $this->resourceConnection = $resourceConnection;

        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setDefaultSort(false);

        $this->setId('ebayListingViewGrid' . $this->listing->getId());

        $this->showAdvancedFilterProductsOption = false;
    }

    //########################################

    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if ($collection) {
            $columnIndex = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
            $collection->getSelect()->order($columnIndex . ' ' . strtoupper($column->getDir()));
        }
        return $this;
    }

    //########################################

    protected function _prepareCollection()
    {
        $listingData = $this->listing->getData();

        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();

        $collection->setListingProductModeOn();
        $collection->setListing($this->listing);
        $collection->setStoreId($this->listing->getStoreId());

        $collection->addAttributeToSelect('sku');
        $collection->addAttributeToSelect('name');

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['lp' => $lpTable],
            'product_id=entity_id',
            [
                'id' => 'id',
                'status' => 'status',
                'component_mode' => 'component_mode',
                'additional_data' => 'additional_data'
            ],
            '{{table}}.listing_id=' . (int)$listingData['id']
        );

        $elpTable = $this->activeRecordFactory->getObject('Ebay_Listing_Product')->getResource()->getMainTable();
        $collection->joinTable(
            ['elp' => $elpTable],
            'listing_product_id=id',
            [
                'listing_product_id' => 'listing_product_id',
                'end_date' => 'end_date',
                'start_date' => 'start_date',
                'online_title' => 'online_title',
                'online_sku' => 'online_sku',
                'available_qty' => new \Zend_Db_Expr('(elp.online_qty - elp.online_qty_sold)'),
                'ebay_item_id' => 'ebay_item_id',
                'online_main_category' => 'online_main_category',
                'online_qty_sold' => 'online_qty_sold',
                'online_bids' => 'online_bids',
                'online_start_price' => 'online_start_price',
                'online_current_price' => 'online_current_price',
                'online_reserve_price' => 'online_reserve_price',
                'online_buyitnow_price' => 'online_buyitnow_price',
                'template_category_id' => 'template_category_id',

                'is_duplicate' => 'is_duplicate',
            ]
        );

        $eiTable = $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable();
        $collection->joinTable(
            ['ei' => $eiTable],
            'id=ebay_item_id',
            [
                'item_id' => 'item_id',
            ],
            null,
            'left'
        );

        if ($this->isFilterOrSortByPriceIsUsed('price', 'ebay_online_current_price')) {
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
            'header'   => $this->__('Product ID'),
            'align'    => 'right',
            'width'    => '100px',
            'type'     => 'number',
            'index'    => 'entity_id',
            'store_id' => $this->listing->getStoreId(),
            'renderer' => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId',
        ]);

        $this->addColumn('name', [
            'header'         => $this->__('Product Title / Product SKU / eBay Category'),
            'align'          => 'left',
            'type'           => 'text',
            'index'          => 'online_title',
            'escape'         => false,
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('ebay_item_id', [
            'header'         => $this->__('Item ID'),
            'align'          => 'left',
            'width'          => '100px',
            'type'           => 'text',
            'index'          => 'item_id',
            'account_id'     => $this->listing->getAccountId(),
            'marketplace_id' => $this->listing->getMarketplaceId(),
            'renderer'       => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\ItemId'
        ]);

        $this->addColumn('available_qty', [
            'header'            => $this->__('Available QTY'),
            'align'             => 'right',
            'width'             => '50px',
            'type'              => 'number',
            'index'             => 'available_qty',
            'sortable'          => true,
            'filter_index'      => 'online_qty',
            'renderer'          => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty',
            'render_online_qty' => OnlineQty::ONLINE_AVAILABLE_QTY,
            'filter_condition_callback' => [$this, 'callbackFilterAvailableQty']
        ]);

        $this->addColumn('online_qty_sold', [
            'header'   => $this->__('Sold QTY'),
            'align'    => 'right',
            'width'    => '50px',
            'type'     => 'number',
            'index'    => 'online_qty_sold',
            'renderer' => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Qty'
        ]);

        $dir = $this->getParam($this->getVarNameDir(), $this->_defaultDir);

        if ($dir == 'desc') {
            $priceSortField = 'max_online_price';
        } else {
            $priceSortField = 'min_online_price';
        }

        $this->addColumn('price', [
            'header'       => $this->__('Price'),
            'align'        => 'right',
            'width'        => '50px',
            'type'         => 'number',
            'currency'     => $this->listing->getMarketplace()->getChildObject()->getCurrency(),
            'index'        => $priceSortField,
            'filter_index' => $priceSortField,
            'renderer'     => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\MinMaxPrice',
            'filter_condition_callback' => [$this, 'callbackFilterPrice']
        ]);

        $this->addColumn('end_date', [
            'header'      => $this->__('End Date'),
            'align'       => 'right',
            'width'       => '150px',
            'type'        => 'datetime',
            'filter'      => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime',
            'format'      => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'       => 'end_date',
            'renderer'    => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\DateTime'
        ]);

        $statusColumn = [
            'header'       => $this->__('Status'),
            'width'        => '100px',
            'index'        => 'status',
            'filter_index' => 'status',
            'type'         => 'options',
            'sortable'     => false,
            'options'      => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED => $this->__('Not Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Listed'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN => $this->__('Listed (Hidden)'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD => $this->__('Sold'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Stopped'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED => $this->__('Finished'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Pending')
            ],
            'showLogIcon' => true,
            'renderer'    => '\Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Renderer\Status',
            'filter_condition_callback' => [$this, 'callbackFilterStatus']
        ];

        if ($this->getHelper('View\Ebay')->isDuplicatesFilterShouldBeShown($this->listing->getId())) {
            $statusColumn['filter'] = 'Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\Status';
        }
        $this->addColumn('status', $statusColumn);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // Configure groups
        // ---------------------------------------

        $groups = [
            'actions' => $this->__('Listing Actions'),
            'other' => $this->__('Other'),
        ];

        $this->getMassactionBlock()->setGroups($groups);

        // Set mass-action
        // ---------------------------------------

        $this->getMassactionBlock()->addItem('list', [
            'label' => $this->__('List Item(s) on eBay'),
            'url' => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('revise', [
            'label' => $this->__('Revise Item(s) on eBay'),
            'url' => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('relist', [
            'label' => $this->__('Relist Item(s) on eBay'),
            'url' => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('stop', [
            'label' => $this->__('Stop Item(s) on eBay'),
            'url' => '',
        ], 'actions');

        $this->getMassactionBlock()->addItem('stopAndRemove', [
            'label' => $this->__('Stop on eBay / Remove From Listing'),
            'url' => ''
        ], 'actions');

        $this->getMassactionBlock()->addItem('previewItems', [
            'label' => $this->__('Preview Items'),
            'url' => ''
        ], 'other');
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $title = $row->getName();

        $onlineTitle = $row->getData('online_title');
        !empty($onlineTitle) && $title = $onlineTitle;

        $title = $this->getHelper('Data')->escapeHtml($title);

        $valueHtml = '<span class="product-title-value">' . $title . '</span>';

        $sku = $row->getData('sku');

        if ($row->getData('sku') === null) {
            $sku = $this->modelFactory->getObject('Magento\Product')
                ->setProductId($row->getData('entity_id'))
                ->getSku();
        }

        $onlineSku = $row->getData('online_sku');
        !empty($onlineSku) && $sku = $onlineSku;

        $valueHtml .= '<br/>' .
            '<strong>' . $this->__('SKU') . ':</strong>&nbsp;' .
            $this->getHelper('Data')->escapeHtml($sku);

        if ($category = $row->getData('online_main_category')) {
            $valueHtml .= '<br/><br/>' .
                '<strong>' . $this->__('Category') . ':</strong>&nbsp;' .
                $this->getHelper('Data')->escapeHtml($category);
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $row->getData('listing_product_id'));

        if (!$listingProduct->getChildObject()->isVariationsReady()) {
            return $valueHtml;
        }

        $additionalData = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));

        $productAttributes = isset($additionalData['variations_sets'])
            ? array_keys($additionalData['variations_sets']) : [];

        $valueHtml .= '<div style="font-size: 11px; font-weight: bold; color: grey; margin: 7px 0 0 7px">';
        $valueHtml .= implode(', ', $productAttributes);
        $valueHtml .= '</div>';

        $linkContent = $this->__('Manage Variations');
        $vpmt = $this->getHelper('Data')->escapeJs(
            $this->__('Manage Variations of "'. $title . '" ')
        );
        $itemId = $this->getData('item_id');

        if (!empty($itemId)) {
            $vpmt .= '(' . $itemId . ')';
        }

        $linkTitle = $this->__('Open Manage Variations Tool');
        $listingProductId = (int)$row->getData('listing_product_id');

        $valueHtml .= <<<HTML
<div style="float: left; margin: 0 0 0 7px">
<a href="javascript:"
onclick="EbayListingViewEbayGridObj.variationProductManageHandler.openPopUp(
        {$listingProductId}, '{$this->getHelper('Data')->escapeHtml($vpmt)}'
    )"
title="{$linkTitle}">{$linkContent}</a>&nbsp;
</div>
HTML;

        if ($childVariationIds = $this->getRequest()->getParam('child_variation_ids')) {
            $this->js->add(<<<JS
    (function() {

         Event.observe(window, 'load', function() {
             EbayListingViewEbayGridObj.variationProductManageHandler.openPopUp(
                {$listingProductId}, '{$vpmt}', 'searched_by_child', '{$childVariationIds}'
             );
         });
    })();
JS
            );
        }

        return $valueHtml;
    }

    private function getItemFeeHtml($row)
    {
        if ($row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED ||
            $row->getData('status') == \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN) {
            $additionalData = (array)$this->getHelper('Data')->jsonDecode($row->getData('additional_data'));

            if (empty($additionalData['ebay_item_fees']['listing_fee']['fee'])) {
                $price = $this->modelFactory->getObject('Currency')->formatPrice(
                    $this->listing->getMarketplace()->getChildObject()->getCurrency(),
                    0
                );

                return <<<HTML
<div style="font-size: 11px">{$this->__('eBay Fee')}: {$price}</div>
HTML;
            }

            $fee = $this->createBlock('Ebay_Listing_View_Ebay_Fee_Product');
            $fee->setData('fees', $additionalData['ebay_item_fees']);
            $fee->setData('product_name', $row->getData('name'));

            return <<<HTML
<div style="font-size: 11px">{$this->__('eBay Fee')}: {$fee->toHtml()}</div>
HTML;
        }

        $listingProductId = (int)$row->getData('listing_product_id');
        $label = $this->__('estimate fee');

        return <<<HTML
<div style="font-size: 11px">
    <a href="javascript:void(0);" class="ebay-fee"
        onclick="EbayListingViewEbayGridObj.getEstimatedFees({$listingProductId});">{$label}</a>
</div>
HTML;
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
                ['attribute' => 'online_sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'name', 'like' => '%' . $value . '%'],
                ['attribute' => 'online_title', 'like' => '%' . $value . '%'],
                ['attribute' => 'online_main_category', 'like' => '%' . $value . '%']
            ]
        );
    }

    protected function callbackFilterAvailableQty($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $where = '';
        $availableQty = 'elp.online_qty - elp.online_qty_sold';

        if (isset($cond['from']) || isset($cond['to'])) {
            if (isset($cond['from']) && $cond['from'] != '') {
                $value = $collection->getConnection()->quote($cond['from']);
                $where .= "{$availableQty} >= {$value}";
            }

            if (isset($cond['to']) && $cond['to'] != '') {
                if (isset($cond['from']) && $cond['from'] != '') {
                    $where .= ' AND ';
                }
                $value = $collection->getConnection()->quote($cond['to']);
                $where .= "{$availableQty} <= {$value}";
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

        if (isset($value['from']) && $value['from'] != '') {
            $condition = 'min_online_price >= \'' . (float)$value['from'] . '\'';
        }
        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $condition .= ' AND ';
            }
            $condition .= 'min_online_price <= \'' . (float)$value['to'] . '\'';
        }

        $condition = '(' . $condition . ') OR (';

        if (isset($value['from']) && $value['from'] != '') {
            $condition .= 'max_online_price >= \'' . (float)$value['from'] . '\'';
        }
        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $condition .= ' AND ';
            }
            $condition .= 'max_online_price <= \'' . (float)$value['to'] . '\'';
        }

        $condition .= ')';

        $collection->getSelect()->having($condition);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        $index = $column->getIndex();

        if ($value == null) {
            return;
        }

        if (is_array($value) && isset($value['value'])) {
            $collection->addFieldToFilter($index, (int)$value['value']);
        } else if (!is_array($value) && $value !== null) {
            $collection->addFieldToFilter($index, (int)$value);
        }

        if (is_array($value) && isset($value['is_duplicate'])) {
            $collection->addFieldToFilter('is_duplicate', 1);
        }
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing/view', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    public function getTooltipHtml($content, $id = '')
    {
        return <<<HTML
<div id="{$id}" class="m2epro-field-tooltip admin__field-tooltip">
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
                EbayListingViewEbayGridObj.afterInitPage();
JS
            );

            return parent::_toHtml();
        }

        $component = \Ess\M2ePro\Helper\Component\Ebay::NICK;

        $temp = $this->getHelper('Data\Session')->getValue('products_ids_for_list', true);
        $productsIdsForList = empty($temp) ? '' : $temp;

        $gridId = $component . 'ListingViewGrid' . $this->listing['id'];
        $ignoreListings = $this->getHelper('Data')->jsonEncode([$this->listing['id']]);

        $this->jsUrl->addUrls([
            'runListProducts' => $this->getUrl('*/ebay_listing/runListProducts'),
            'runRelistProducts' => $this->getUrl('*/ebay_listing/runRelistProducts'),
            'runReviseProducts' => $this->getUrl('*/ebay_listing/runReviseProducts'),
            'runStopProducts' => $this->getUrl('*/ebay_listing/runStopProducts'),
            'runStopAndRemoveProducts' => $this->getUrl('*/ebay_listing/runStopAndRemoveProducts'),
            'previewItems' => $this->getUrl('*/ebay_listing/previewItems'),
        ]);

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Ebay_Listing_Product_Duplicate'));

        $this->jsUrl->add($this->getUrl('*/ebay_listing/getEstimatedFees'), 'ebay_listing/getEstimatedFees');
        $this->jsUrl->add(
            $this->getUrl('*/ebay_listing/getCategoryChooserHtml', [
                'listing_id' => $this->listing['id']
            ]),
            'ebay_listing/getCategoryChooserHtml'
        );
        $this->jsUrl->add(
            $this->getUrl('*/ebay_listing/saveCategoryTemplate', [
                'listing_id' => $this->listing['id']
            ]),
            'ebay_listing/saveCategoryTemplate'
        );

        $this->jsUrl->add($this->getUrl('*/ebay_log_listing_product/index'), 'ebay_log_listing_product/index');

        $this->jsUrl->add(
            $this->getUrl('*/ebay_log_listing_product/index', [
                \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid::LISTING_ID_FIELD =>
                    $this->listing['id'],
                'back' => $this->getHelper('Data')->makeBackUrlParam(
                    '*/ebay_listing/view',
                    ['id' => $this->listing['id']]
                )
            ]),
            'logViewUrl'
        );
        $this->jsUrl->add($this->getUrl('*/listing/getErrorsSummary'), 'getErrorsSummary');

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Listing\Moving'));
        $this->jsUrl->add(
            $this->getUrl('*/ebay_listing_moving/moveToListingGrid'),
            'ebay_listing_moving/moveToListingGrid'
        );

        $this->jsUrl->add($this->getUrl('*/ebay_listing/getListingProductBids'), 'ebay_listing/getListingProductBids');

        $taskCompletedWarningMessage = '"%task_title%" task has completed with warnings. ';
        $taskCompletedWarningMessage .= '<a target="_blank" href="%url%">View Log</a> for details.';

        $taskCompletedErrorMessage = '"%task_title%" task has completed with errors. ';
        $taskCompletedErrorMessage .= '<a target="_blank" href="%url%">View Log</a> for details.';

        $this->jsTranslator->addTranslations([
            'task_completed_message' => $this->__('Task completed. Please wait ...'),

            'task_completed_success_message' => $this->__('"%task_title%" task has completed.'),

            'task_completed_warning_message' => $this->__($taskCompletedWarningMessage),
            'task_completed_error_message' => $this->__($taskCompletedErrorMessage),

            'sending_data_message' => $this->__('Sending %product_title% Product(s) data on eBay.'),

            'View Full Product Log' => $this->__('View Full Product Log.'),

            'The Listing was locked by another process. Please try again later.' =>
                $this->__('The Listing was locked by another process. Please try again later.'),

            'Listing is empty.' => $this->__('Listing is empty.'),

            'listing_all_items_message' => $this->__('Listing All Items On eBay'),
            'listing_selected_items_message' => $this->__('Listing Selected Items On eBay'),
            'revising_selected_items_message' => $this->__('Revising Selected Items On eBay'),
            'relisting_selected_items_message' => $this->__('Relisting Selected Items On eBay'),
            'stopping_selected_items_message' => $this->__('Stopping Selected Items On eBay'),
            'stopping_and_removing_selected_items_message' => $this->__(
                'Stopping On eBay And Removing From Listing Selected Items'
            ),
            'removing_selected_items_message' => $this->__('Removing From Listing Selected Items'),

            'Please select the Products you want to perform the Action on.' =>
                $this->__('Please select the Products you want to perform the Action on.'),

            'Please select Action.' => $this->__('Please select Action.'),

            'Moving eBay Item' => $this->__('Moving eBay Item'),
            'Moving eBay Items' => $this->__('Moving eBay Items'),
            'Specifics' => $this->__('Specifics'),
            'Ebay Item Duplicate' => $this->__('eBay Item Duplicate')
        ]);

        $showAutoAction = $this->getHelper('Data')->jsonEncode((bool)$this->getRequest()->getParam('auto_actions'));

        $this->js->add(
            <<<JS
    M2ePro.productsIdsForList = '{$productsIdsForList}';

    M2ePro.customData.componentMode = '{$component}';
    M2ePro.customData.gridId = '{$gridId}';
    M2ePro.customData.ignoreListings = '{$ignoreListings}';
JS
        );

        $this->js->addOnReadyJs(
            <<<JS
    require([
        'EbayListingAutoActionInstantiation',
        'M2ePro/Ebay/Listing/View/Ebay/Grid',
        'M2ePro/Ebay/Listing/VariationProductManage'
    ], function(){

        window.EbayListingViewEbayGridObj = new EbayListingViewEbayGrid(
            '{$this->getId()}',
            {$this->listing['id']}
        );
        EbayListingViewEbayGridObj.afterInitPage();

        EbayListingViewEbayGridObj.actionHandler.setProgressBar('listing_view_progress_bar');
        EbayListingViewEbayGridObj.actionHandler.setGridWrapper('listing_view_content_container');

        if (M2ePro.productsIdsForList) {
            EbayListingViewEbayGridObj.getGridMassActionObj().checkedString = M2ePro.productsIdsForList;
            EbayListingViewEbayGridObj.actionHandler.listAction();
        }

        if ({$showAutoAction}) {
            wait(
                function() { return typeof ListingAutoActionObj != 'undefined'; },
                function () { ListingAutoActionObj.loadAutoActionHtml(); },
                50
            );
        }

    });
JS
        );

        return parent::_toHtml();
    }

    //########################################

    private function convertAndFormatPriceCurrency($price, $currency)
    {
        return $this->localeCurrency->getCurrency($currency)->toCurrency($price);
    }

    //########################################
}
