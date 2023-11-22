<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

class Grid extends AbstractGrid
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Item\Collection */
    private $itemsCollection;

    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Note\Collection */
    protected $notesCollection;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    protected $walmartFactory;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;
    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\FilterFactory */
    private $advancedFilterFactory;
    /** @var \Ess\M2ePro\Model\Walmart\AdvancedFilter\AllOrdersOptions */
    private $advancedFilterAllOrdersOptions;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\FilterFactory $advancedFilterFactory,
        \Ess\M2ePro\Model\Walmart\AdvancedFilter\AllOrdersOptions $advancedFilterAllOrdersOptions,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->walmartFactory = $walmartFactory;
        $this->databaseHelper = $databaseHelper;
        $this->dataHelper = $dataHelper;
        $this->walmartHelper = $walmartHelper;
        $this->advancedFilterFactory = $advancedFilterFactory;
        $this->advancedFilterAllOrdersOptions = $advancedFilterAllOrdersOptions;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartOrderGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('purchase_create_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    public function _prepareAdvancedFilters()
    {
        parent::_prepareAdvancedFilters();
        $this->addMarketplaceAdvancedFilter();
        $this->addAccountAdvancedFilter();
        $this->addMagentoOrderCreatedFilter();
        $this->addIsWfsFilter();
    }
    protected function _prepareCollection()
    {
        $collection = $this->walmartFactory->getObject('Order')->getCollection();

        $collection->getSelect()
                   ->joinLeft(
                       ['so' => $this->databaseHelper->getTableNameWithPrefix('sales_order')],
                       '(so.entity_id = `main_table`.magento_order_id)',
                       ['magento_order_num' => 'increment_id']
                   );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        $this->itemsCollection = $this->walmartFactory->getObject('Order\Item')
                                                      ->getCollection()
                                                      ->addFieldToFilter(
                                                          'order_id',
                                                          ['in' => $this->getCollection()->getColumnValues('id')]
                                                      );

        $this->notesCollection = $this->activeRecordFactory->getObject('Order\Note')
                                                           ->getCollection()
                                                           ->addFieldToFilter(
                                                               'order_id',
                                                               ['in' => $this->getCollection()->getColumnValues('id')]
                                                           );

        return parent::_afterLoadCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'purchase_create_date',
            [
                'header' => __('Sale Date'),
                'align' => 'left',
                'type' => 'datetime',
                'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
                'format' => \IntlDateFormatter::MEDIUM,
                'filter_time' => true,
                'index' => 'purchase_create_date',
                'width' => '170px',
                'frame_callback' => [$this, 'callbackPurchaseCreateDate'],
            ]
        );

        $this->addColumn(
            'shipping_date_to',
            [
                'header' => __('Ship By Date'),
                'align' => 'left',
                'type' => 'datetime',
                'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
                'format' => \IntlDateFormatter::MEDIUM,
                'filter_time' => true,
                'index' => 'shipping_date_to',
                'width' => '170px',
                'frame_callback' => [$this, 'callbackShippingDateTo'],
            ]
        );

        $this->addColumn(
            'magento_order_num',
            [
                'header' => __('Magento Order #'),
                'align' => 'left',
                'index' => 'so.increment_id',
                'width' => '110px',
                'frame_callback' => [$this, 'callbackColumnMagentoOrder'],
            ]
        );

        $this->addColumn(
            'walmart_order_id',
            [
                'header' => __('Walmart Purchase Order #'),
                'align' => 'left',
                'width' => '110px',
                'index' => 'walmart_order_id',
                'frame_callback' => [$this, 'callbackColumnWalmartOrderId'],
                'filter_condition_callback' => [$this, 'callbackFilterOrders'],
            ]
        );

        $this->addColumn(
            'walmart_order_items',
            [
                'header' => __('Items'),
                'align' => 'left',
                'index' => 'walmart_order_items',
                'sortable' => false,
                'width' => '*',
                'frame_callback' => [$this, 'callbackColumnItems'],
                'filter_condition_callback' => [$this, 'callbackFilterItems'],
            ]
        );

        $this->addColumn(
            'buyer',
            [
                'header' => __('Buyer'),
                'align' => 'left',
                'index' => 'buyer_name',
                'width' => '120px',
                'frame_callback' => [$this, 'callbackColumnBuyer'],
                'filter_condition_callback' => [$this, 'callbackFilterBuyer'],
            ]
        );

        $this->addColumn(
            'is_wfs',
            [
                'header' => __('Fulfillment'),
                'width' => '120px',
                'index' => 'is_wfs',
                'filter_index' => 'second_table.is_wfs',
                'type' => 'options',
                'sortable' => false,
                'options' => [
                    0 => __('Merchant'),
                    1 => __('WALMART'),
                ],
                'frame_callback' => [$this, 'callbackColumnIsWFS'],
            ]
        );

        $this->addColumn(
            'paid_amount',
            [
                'header' => __('Total Paid'),
                'align' => 'left',
                'width' => '110px',
                'index' => 'paid_amount',
                'type' => 'number',
                'frame_callback' => [$this, 'callbackColumnTotal'],
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'align' => 'left',
                'width' => '50px',
                'index' => 'status',
                'filter_index' => 'second_table.status',
                'type' => 'options',
                'options' => [
                    \Ess\M2ePro\Model\Walmart\Order::STATUS_CREATED => __('Created'),
                    \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED => __('Unshipped'),
                    \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED_PARTIALLY => __('Partially Shipped'),
                    \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED => __('Shipped'),
                    \Ess\M2ePro\Model\Walmart\Order::STATUS_CANCELED => __('Canceled'),
                ],
                'frame_callback' => [$this, 'callbackColumnStatus'],
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------

        // Set mass-action
        // ---------------------------------------

        $this->getMassactionBlock()->addItem(
            'ship',
            [
                'label' => __('Mark Order(s) as Shipped'),
                'url' => $this->getUrl('*/walmart_order/updateShippingStatus'),
                'confirm' => __('Are you sure?'),
            ]
        );

        $this->getMassactionBlock()->addItem(
            'resend_shipping',
            [
                'label' => __('Resend Shipping Information'),
                'url' => $this->getUrl('*/order/resubmitShippingInfo'),
                'confirm' => __('Are you sure?'),
            ]
        );

        $this->getMassactionBlock()->addItem(
            'create_order',
            [
                'label' => __('Create Magento Order'),
                'url' => $this->getUrl('*/walmart_order/createMagentoOrder'),
                'confirm' => __('Are you sure?'),
            ]
        );

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    public function callbackPurchaseCreateDate($value, $row, $column, $isExport)
    {
        return $this->_localeDate->formatDate(
            $row->getChildObject()->getData('purchase_create_date'),
            \IntlDateFormatter::MEDIUM,
            true
        );
    }

    public function callbackShippingDateTo($value, $row, $column, $isExport)
    {
        return $this->_localeDate->formatDate(
            $row->getChildObject()->getData('shipping_date_to'),
            \IntlDateFormatter::MEDIUM,
            true
        );
    }

    public function callbackColumnWalmartOrderId($value, $row, $column, $isExport)
    {
        $back = $this->dataHelper->makeBackUrlParam('*/walmart_order/index');
        $itemUrl = $this->getUrl('*/walmart_order/view', ['id' => $row->getId(), 'back' => $back]);
        $walmartOrderId = $this->dataHelper->escapeHtml($row->getChildObject()->getData('walmart_order_id'));

        $returnString = "<a href=\"{$itemUrl}\">{$walmartOrderId}</a>";

        $customerOrderId = $this->dataHelper->escapeHtml($row->getChildObject()->getData('customer_order_id'));
        if (!empty($customerOrderId)) {
            $returnString .= sprintf('<br>[ <b>%s</b> %s ]', __('CO #'), $customerOrderId);
        }

        /** @var \Ess\M2ePro\Model\Order\Note[] $notes */
        $notes = $this->notesCollection->getItemsByColumnValue('order_id', $row->getData('id'));
        $returnString .= $this->formatNotes($notes);

        return $returnString;
    }

    /**
     * @param string $text
     * @param int $maxLength
     *
     * @return string
     */
    private function cutText(string $text, int $maxLength): string
    {
        return mb_strlen($text) > $maxLength ? mb_substr($text, 0, $maxLength) . "..." : $text;
    }

    /**
     * @param $notes
     *
     * @return string
     */
    private function formatNotes($notes)
    {
        $notesHtml = '';
        $maxLength = 250;

        if (!$notes) {
            return '';
        }

        $notesHtml .= <<<HTML
    <div class="note_icon admin__field-tooltip">
        <a class="admin__field-tooltip-note-action" href="javascript://"></a>
        <div class="admin__field-tooltip-content" style="right: -4.4rem">
            <div class="walmart-identifiers">
HTML;

        if (count($notes) === 1) {
            $noteValue = $notes[0]->getNote();
            $shortenedNote = $this->cutText($noteValue, $maxLength);
            $notesHtml .= "<div>{$shortenedNote}</div>";
        } else {
            $notesHtml .= "<ul>";
            foreach ($notes as $note) {
                $noteValue = $note->getNote();
                $shortenedNote = $this->cutText($noteValue, $maxLength);
                $notesHtml .= "<li>{$shortenedNote}</li>";
            }
            $notesHtml .= "</ul>";
        }

        $notesHtml .= <<<HTML
            </div>
        </div>
    </div>
HTML;

        return $notesHtml;
    }

    public function callbackColumnMagentoOrder($value, $row, $column, $isExport)
    {
        $magentoOrderId = $row['magento_order_id'];
        $returnString = __('N/A');

        if ($magentoOrderId !== null) {
            if ($row['magento_order_num']) {
                $magentoOrderNumber = $this->dataHelper->escapeHtml($row['magento_order_num'] ?? '');
                $orderUrl = $this->getUrl('sales/order/view', ['order_id' => $magentoOrderId]);
                $returnString = '<a href="' . $orderUrl . '" target="_blank">' . $magentoOrderNumber . '</a>';
            } else {
                $returnString = '<span style="color: red;">' . __('Deleted') . '</span>';
            }
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon\Order $viewLogIcon */
        $viewLogIcon = $this->getLayout()
                            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon\Order::class);
        $logIconHtml = $viewLogIcon->render($row);

        if ($logIconHtml !== '') {
            return '<div style="min-width: 100px">' . $returnString . $logIconHtml . '</div>';
        }

        return $returnString;
    }

    public function callbackColumnItems($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Model\Order\Item[] $items */
        $items = $this->itemsCollection->getItemsByColumnValue('order_id', $row->getData('id'));

        $html = '';
        $gridId = $this->getId();

        foreach ($items as $item) {
            if ($html != '') {
                $html .= '<br/>';
            }

            $isShowEditLink = false;

            try {
                $product = $item->getProduct();
            } catch (\Ess\M2ePro\Model\Exception $e) {
                $product = null;
                /** @var \Ess\M2ePro\Model\Order\Log $logModel */
                $logModel = $this->activeRecordFactory->getObject('Order\Log');
                $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);

                $logModel->addMessage(
                    $row->getData('id'),
                    $e->getMessage(),
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                );
            }

            if ($product !== null) {
                /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
                $magentoProduct = $this->modelFactory->getObject('Magento\Product');
                $magentoProduct->setProduct($product);

                $associatedProducts = $item->getAssociatedProducts();
                $associatedOptions = $item->getAssociatedOptions();

                if (
                    $magentoProduct->isProductWithVariations()
                    && empty($associatedOptions)
                    && empty($associatedProducts)
                ) {
                    $isShowEditLink = true;
                }
            }

            $editItemHtml = '';
            if ($isShowEditLink) {
                $orderItemId = $item->getId();
                $orderItemEditLabel = __('edit');

                $js = "{OrderEditItemObj.edit('{$gridId}', {$orderItemId});}";

                $editItemHtml = <<<HTML
<span>&nbsp;<a href="javascript:void(0);" onclick="{$js}">[{$orderItemEditLabel}]</a></span>
HTML;
            }

            $skuHtml = '';
            if ($item->getChildObject()->getSku()) {
                $skuLabel = __('SKU');
                $sku = $this->dataHelper->escapeHtml($item->getChildObject()->getSku());
                if ($product !== null) {
                    $productUrl = $this->getUrl('catalog/product/edit', ['id' => $product->getId()]);
                    $sku = <<<STRING
<a href="{$productUrl}" target="_blank">{$sku}</a>
STRING;
                }

                $skuHtml = <<<STRING
<span style="padding-left: 10px;"><b>{$skuLabel}:</b>&nbsp;{$sku}</span><br/>
STRING;
            }

            $itemTitle = $this->dataHelper->escapeHtml($item->getChildObject()->getTitle());
            $cancellationRequested = '';
            if (
                $item->getChildObject()->isBuyerCancellationRequested()
                && $item->getChildObject()->isBuyerCancellationPossible()
            ) {
                $cancellationRequested = <<<HTML
<span style="color: red;">Cancellation Requested</span><br/>
HTML;
            }

            $qtyLabel = __('QTY');
            $qtyHtml = <<<HTML
<span style="padding-left: 10px;"><b>{$qtyLabel}:</b> {$item->getChildObject()->getQtyPurchased()}</span>
HTML;

            $html .= <<<HTML
{$itemTitle}&nbsp;{$editItemHtml}<br/>
{$cancellationRequested}
<small>{$skuHtml}{$qtyHtml}</small>
HTML;
        }

        return $html;
    }

    public function callbackColumnBuyer($value, $row, $column, $isExport)
    {
        if ($row->getChildObject()->getData('buyer_name') == '') {
            return __('N/A');
        }

        return $this->dataHelper->escapeHtml($row->getChildObject()->getData('buyer_name'));
    }

    public function callbackColumnIsWFS($value, $row, $column, $isExport)
    {
        if (
            (int)$row->getChildObject()->getData('is_wfs') === 1
        ) {
            return '<span style="font-weight: bold;">' . __('Walmart') . '</span>';
        }

        return __('Merchant');
    }

    public function callbackColumnTotal($value, $row, $column, $isExport)
    {
        $currency = $row->getChildObject()->getData('currency');

        if (empty($currency)) {
            /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
            $marketplace = $this->walmartFactory->getCachedObjectLoaded(
                'Marketplace',
                $row->getData('marketplace_id')
            );
            /** @var \Ess\M2ePro\Model\Walmart\Marketplace $walmartMarketplace */
            $walmartMarketplace = $marketplace->getChildObject();

            $currency = $walmartMarketplace->getDefaultCurrency();
        }

        return $this->modelFactory->getObject('Currency')->formatPrice(
            $currency,
            $row->getChildObject()->getData('paid_amount')
        );
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $statuses = [
            \Ess\M2ePro\Model\Walmart\Order::STATUS_CREATED => __('Created'),
            \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED => __('Unshipped'),
            \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED_PARTIALLY => __('Partially Shipped'),
            \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED => __('Shipped'),
            \Ess\M2ePro\Model\Walmart\Order::STATUS_CANCELED => __('Canceled'),
        ];
        $status = $row->getChildObject()->getData('status');

        $value = $statuses[$status];

        $statusColors = [
            \Ess\M2ePro\Model\Walmart\Order::STATUS_CREATED => 'gray',
            \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED => 'green',
            \Ess\M2ePro\Model\Walmart\Order::STATUS_CANCELED => 'red',
        ];

        $color = isset($statusColors[$status]) ? $statusColors[$status] : 'black';
        $value = '<span style="color: ' . $color . ';">' . $value . '</span>';

        if ($row->isSetProcessingLock('update_order_status')) {
            $value .= '<br/>';
            $value .= '<span style="color: gray;">['
                . __('Status Update in Progress...') . ']</span>';
        }

        return $value;
    }

    protected function callbackFilterOrders($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            "second_table.walmart_order_id LIKE ? OR second_table.customer_order_id LIKE ?",
            '%' . $value . '%'
        );
    }

    protected function callbackFilterItems($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $orderItemsCollection = $this->walmartFactory->getObject('Order\Item')->getCollection();

        $orderItemsCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $orderItemsCollection->getSelect()->columns('order_id');
        $orderItemsCollection->getSelect()->distinct(true);

        $orderItemsCollection->getSelect()->where('title LIKE ? OR sku LIKE ?', '%' . $value . '%');

        $totalResult = $orderItemsCollection->getColumnValues('order_id');
        $collection->addFieldToFilter('main_table.id', ['in' => $totalResult]);
    }

    protected function callbackFilterBuyer($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection
            ->getSelect()
            ->where('buyer_email LIKE ? OR buyer_name LIKE ?', '%' . $value . '%');
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/walmart_order/grid', ['_current' => true]);
    }

    public function getRowUrl($item)
    {
        return false;
    }

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->addOnReadyJs(
                <<<JS
    OrderObj.initializeGrids();
JS
            );

            return parent::_toHtml();
        }

        $tempGridIds = [];
        $this->walmartHelper->isEnabled() && $tempGridIds[] = $this->getId();

        $tempGridIds = \Ess\M2ePro\Helper\Json::encode($tempGridIds);

        $this->jsPhp->addConstants(
            $this->dataHelper
                ->getClassConstants(\Ess\M2ePro\Model\Log\AbstractModel::class)
        );

        $this->jsUrl->addUrls(
            [
                'walmart_order/view' => $this->getUrl(
                    '*/walmart_order/view',
                    ['back' => $this->dataHelper->makeBackUrlParam('*/walmart_order/index')]
                ),
            ]
        );

        $this->jsTranslator->add('View Full Order Log', __('View Full Order Log'));

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Order',
    ], function(){
        window.OrderObj = new Order('$tempGridIds');
        OrderObj.initializeGrids();
    });
JS
        );

        return parent::_toHtml();
    }

    private function addMarketplaceAdvancedFilter(): void
    {
        $options = $this->advancedFilterAllOrdersOptions->getMarketplaceOptions();
        if ($options->isEmpty()) {
            return;
        }

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Order\Collection $orders,
            string $filterValue
        ) {
            if (empty($filterValue)) {
                return;
            }

            $orders->addFieldToFilter('marketplace_id', ['eq' => (int)$filterValue]);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'marketplace',
            __('Marketplace'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addAccountAdvancedFilter(): void
    {
        $options = $this->advancedFilterAllOrdersOptions->getAccountOptions();

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Order\Collection $orders,
            string $filterValue
        ): void {
            if (empty($filterValue)) {
                return;
            }

            $orders->addFieldToFilter('account_id', ['eq' => (int)$filterValue]);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'account',
            __('Account'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addMagentoOrderCreatedFilter(): void
    {
        $options = $this->advancedFilterAllOrdersOptions->getYesNoOptions();

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Order\Collection $orders,
            string $filterValue
        ): void {
            if ((int)$filterValue === 1) {
                $orders->addFieldToFilter('magento_order_id', ['neq' => null]);
            } elseif ((int)$filterValue === 0) {
                $orders->addFieldToFilter('magento_order_id', ['null' => true]);
            }
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'magento_order_id',
            __('Magento Order created'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addIsWfsFilter(): void
    {
        $options = $this->advancedFilterAllOrdersOptions->getYesNoOptions();

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Order\Collection $orders,
            string $filterValue
        ): void {
            $orders->addFieldToFilter('is_wfs', ['eq' => $filterValue]);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'is_wfs',
            __('WFS'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }
}
