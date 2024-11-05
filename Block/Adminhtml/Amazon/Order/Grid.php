<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;
use Ess\M2ePro\Model\Amazon\Listing\Product;

class Grid extends AbstractGrid
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Item\Collection $itemsCollection */
    private $itemsCollection;

    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Note\Collection $notesCollection */
    protected $notesCollection;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    protected $amazonFactory;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;

    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\FilterFactory */
    private $advancedFilterFactory;
    /** @var \Ess\M2ePro\Model\Amazon\AdvancedFilter\AllOrdersOptions */
    private $advancedFilterAllOrdersOptions;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Model\Amazon\AdvancedFilter\AllOrdersOptions $advancedFilterAllOrdersOptions,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\FilterFactory $advancedFilterFactory,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->amazonFactory = $amazonFactory;
        $this->databaseHelper = $databaseHelper;
        $this->dataHelper = $dataHelper;
        $this->amazonHelper = $amazonHelper;
        $this->advancedFilterAllOrdersOptions = $advancedFilterAllOrdersOptions;
        $this->advancedFilterFactory = $advancedFilterFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonOrderGrid');

        $this->setDefaultSort('purchase_create_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    public function getMassactionBlockName()
    {
        return \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Massaction::class;
    }

    public function _prepareAdvancedFilters()
    {
        parent::_prepareAdvancedFilters();
        $this->addMarketplaceAdvancedFilter();
        $this->addAccountAdvancedFilter();
        $this->addMagentoOrderCreatedFilter();
        $this->addInvoiceSentFilter();
        $this->addCreditMemoSentFilter();
        $this->addPrimeFilter();
        $this->addB2BFilter();
        $this->addInvoiceByAmazonFilter();
    }

    protected function _prepareCollection()
    {
        $collection = $this->amazonFactory->getObject('Order')->getCollection();

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
        $this->itemsCollection = $this->amazonFactory->getObject('Order\Item')
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
            'delivery_date',
            [
                'header' => __('Deliver By Date'),
                'align' => 'left',
                'type' => 'datetime',
                'filter' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
                'format' => \IntlDateFormatter::MEDIUM,
                'filter_time' => true,
                'index' => 'delivery_date_from',
                'frame_callback' => [$this, 'callbackDeliveryDate'],
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
            'amazon_order_id',
            [
                'header' => __('Amazon Order #'),
                'align' => 'left',
                'width' => '160px',
                'index' => 'amazon_order_id',
                'frame_callback' => [$this, 'callbackColumnAmazonOrderId'],
            ]
        );

        $this->addColumn(
            'amazon_order_items',
            [
                'header' => __('Items'),
                'align' => 'left',
                'index' => 'amazon_order_items',
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
            'is_afn_channel',
            [
                'header' => __('Fulfillment'),
                'width' => '100px',
                'index' => 'is_afn_channel',
                'filter_index' => 'second_table.is_afn_channel',
                'type' => 'options',
                'sortable' => false,
                'options' => [
                    0 => __('Merchant'),
                    1 => __('Amazon'),
                ],
                'frame_callback' => [$this, 'callbackColumnAfnChannel'],
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
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING => __('Pending'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING_RESERVED =>
                        __('Pending / QTY Reserved'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED => __('Unshipped'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED_PARTIALLY => __('Partially Shipped'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED => __('Shipped'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_INVOICE_UNCONFIRMED => __('Invoice Not Confirmed'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_UNFULFILLABLE => __('Unfulfillable'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED => __('Canceled'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELLATION_REQUESTED =>
                        __('Unshipped (Cancellation Requested)'),
                ],
                'frame_callback' => [$this, 'callbackColumnStatus'],
                'filter_condition_callback' => [$this, 'callbackFilterStatus'],
            ]
        );

        $this->addColumn(
            'actions',
            [
                'header' => __('Actions'),
                'align' => 'left',
                'width' => '100px',
                'type' => 'action',
                'index' => 'actions',
                'filter' => false,
                'sortable' => false,
                'renderer' => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
                'getter' => 'getId',
                'actions' => [
                    [
                        'caption' => __('View'),
                        'url' => [
                            'base' => '*/amazon_order/view',
                        ],
                        'field' => 'id',
                    ],
                    [
                        'caption' => __('Create Magento Order'),
                        'url' => [
                            'base' => '*/amazon_order/createMagentoOrder',
                        ],
                        'field' => 'id',
                    ],
                    [
                        'caption' => __('Mark As Shipped'),
                        'field' => 'id',
                        'onclick_action' => 'AmazonOrderMerchantFulfillmentObj.markAsShippedAction',
                    ],
                    [
                        'caption' => __('Amazon\'s Shipping Services'),
                        'field' => 'id',
                        'onclick_action' => 'AmazonOrderMerchantFulfillmentObj.getPopupAction',
                    ],
                ],
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
            'reservation_place',
            [
                'label' => __('Reserve QTY'),
                'url' => $this->getUrl('*/order/reservationPlace'),
                'confirm' => __('Are you sure?'),
            ]
        );

        $this->getMassactionBlock()->addItem(
            'reservation_cancel',
            [
                'label' => __('Cancel QTY Reserve'),
                'url' => $this->getUrl('*/order/reservationCancel'),
                'confirm' => __('Are you sure?'),
            ]
        );

        $this->getMassactionBlock()->addItem(
            'ship',
            [
                'label' => __('Mark Order(s) as Shipped'),
                'url' => $this->getUrl('*/amazon_order/updateShippingStatus'),
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
            'resend_invoice_creditmemo',
            [
                'label' => __('Resend Invoice / Credit Memo'),
                'url' => $this->getUrl('*/amazon_order/resendInvoiceCreditmemo'),
                'confirm' => __('Are you sure?'),
            ]
        );

        $this->getMassactionBlock()->addItem(
            'create_order',
            [
                'label' => __('Create Magento Order'),
                'url' => $this->getUrl('*/amazon_order/createMagentoOrder'),
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

    public function callbackDeliveryDate($value, $row, $column, $isExport)
    {
        $deliveryDate = $row->getChildObject()->getData('delivery_date_from');
        if (empty($deliveryDate)) {
            return __('N/A');
        }

        return $this->_localeDate->formatDate(
            $deliveryDate,
            \IntlDateFormatter::MEDIUM,
            true
        );
    }

    public function callbackColumnAmazonOrderId($value, $row, $column, $isExport)
    {
        $back = $this->dataHelper->makeBackUrlParam('*/amazon_order/index');
        $itemUrl = $this->getUrl('*/amazon_order/view', ['id' => $row->getId(), 'back' => $back]);

        $orderId = $this->dataHelper->escapeHtml($row->getChildObject()->getData('amazon_order_id'));
        $url = $this->amazonHelper->getOrderUrl($orderId, $row->getData('marketplace_id'));

        $returnString = <<<HTML
<a href="{$itemUrl}">{$orderId}</a>
HTML;

        $primeImageHtml = '';
        if ($row->getChildObject()->getData('is_prime')) {
            $imageURL = $this->getViewFileUrl('Ess_M2ePro::images/prime.png');
            $primeImageHtml = <<<HTML
<div style="margin-top: 2px;"><img src="{$imageURL}" /></div>
HTML;
        }

        $businessImageHtml = '';
        if ($row->getChildObject()->getData('is_business')) {
            $imageURL = $this->getViewFileUrl('Ess_M2ePro::images/amazon-business.png');
            $businessImageHtml = <<<HTML
<div style="margin-top: 2px;"><img src="{$imageURL}" /></div>
HTML;
        }

        $isSoldByAmazonImageHtml = '';
        if ($row->getChildObject()->getData('is_sold_by_amazon')) {
            $imageURL = $this->getViewFileUrl('Ess_M2ePro::images/invoice-by-amazon.png');
            $isSoldByAmazonImageHtml = <<<HTML
<div style="margin-top: 2px;"><img height="22px" src="{$imageURL}" /></div>
HTML;
        }

        $isReplacementOrder = '';
        if ($row->getChildObject()->getReplacedAmazonOrderId()) {
            $isReplacementOrder = '<div class="label-replacement-order">Replacement</div>';
        }

        $returnString .= <<<HTML
<a title="{$this->__('View on Amazon')}" target="_blank" href="{$url}">
<img style="margin-top: 6px; float: right"
 src="{$this->getViewFileUrl('Ess_M2ePro::images/view_amazon.png')}" alt="{$this->__('View on Amazon')}" /></a>
HTML;
        $returnString .= $primeImageHtml;
        $returnString .= $businessImageHtml;
        $returnString .= $isSoldByAmazonImageHtml;
        $returnString .= $isReplacementOrder;

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
            <div class="amazon-identifiers">
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
                $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

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

            $generalIdLabel = __($item->getChildObject()->getIsIsbnGeneralId() ? 'ISBN' : 'ASIN');
            $generalId = $this->dataHelper->escapeHtml($item->getChildObject()->getGeneralId());

            $itemUrl = $this->amazonHelper->getItemUrl(
                $item->getChildObject()->getGeneralId(),
                $row->getData('marketplace_id')
            );

            $itemLink = '<a href="' . $itemUrl . '" target="_blank">' . $generalId . '</a>';

            $generalIdHtml = <<<STRING
<span style="padding-left: 10px;"><b>{$generalIdLabel}:</b>&nbsp;{$itemLink}</span><br/>
STRING;

            $itemTitle = $this->dataHelper->escapeHtml($item->getChildObject()->getTitle());
            $qtyLabel = __('QTY');
            $qtyHtml = <<<HTML
<span style="padding-left: 10px;"><b>{$qtyLabel}:</b> {$item->getChildObject()->getQtyPurchased()}</span>
HTML;

            $html .= <<<HTML
{$itemTitle}&nbsp;{$editItemHtml}<br/>
<small>{$generalIdHtml}{$skuHtml}{$qtyHtml}</small>
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

    public function callbackColumnTotal($value, $row, $column, $isExport)
    {
        $currency = $row->getChildObject()->getData('currency');

        if (empty($currency)) {
            /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
            $marketplace = $this->amazonFactory->getCachedObjectLoaded(
                'Marketplace',
                $row->getData('marketplace_id')
            );
            /** @var \Ess\M2ePro\Model\Amazon\Marketplace $amazonMarketplace */
            $amazonMarketplace = $marketplace->getChildObject();

            $currency = $amazonMarketplace->getDefaultCurrency();
        }

        return $this->modelFactory->getObject('Currency')->formatPrice(
            $currency,
            $row->getChildObject()->getData('paid_amount')
        );
    }

    public function callbackColumnAfnChannel($value, $row, $column, $isExport)
    {
        if (
            $row->getChildObject()->getData('is_afn_channel') == Product::IS_AFN_CHANNEL_YES
        ) {
            return '<span style="font-weight: bold;">' . __('Amazon') . '</span>';
        }

        return __('Merchant');
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $statuses = [
            \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING => __('Pending'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED => __('Unshipped'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED_PARTIALLY => __('Partially Shipped'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED => __('Shipped'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_INVOICE_UNCONFIRMED => __('Invoice Not Confirmed'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_UNFULFILLABLE => __('Unfulfillable'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED => __('Canceled'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELLATION_REQUESTED =>
                __('Unshipped (Cancellation Requested)'),
        ];
        $status = $row->getChildObject()->getData('status');

        $value = $statuses[$status];

        $statusColors = [
            \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING => 'gray',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED => 'green',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED => 'red',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELLATION_REQUESTED => 'red',
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

    protected function callbackFilterItems($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $orderItemsCollection = $this->amazonFactory->getObject('Order\Item')->getCollection();

        $orderItemsCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $orderItemsCollection->getSelect()->columns('order_id');
        $orderItemsCollection->getSelect()->distinct(true);

        $orderItemsCollection->getSelect()->where(
            'title LIKE ? OR sku LIKE ? or general_id LIKE ?',
            '%' . $value . '%'
        );

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

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        switch ($value) {
            case \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING:
                $collection->addFieldToFilter(
                    'second_table.status',
                    [\Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING]
                );
                break;

            case \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED:
                $collection->addFieldToFilter(
                    'second_table.status',
                    [\Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED]
                );
                break;

            case \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED_PARTIALLY:
                $collection->addFieldToFilter(
                    'second_table.status',
                    [\Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED_PARTIALLY]
                );
                break;
            case \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED:
                $collection->addFieldToFilter(
                    'second_table.status',
                    [\Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED]
                );
                break;
            case \Ess\M2ePro\Model\Amazon\Order::STATUS_UNFULFILLABLE:
                $collection->addFieldToFilter(
                    'second_table.status',
                    [\Ess\M2ePro\Model\Amazon\Order::STATUS_UNFULFILLABLE]
                );
                break;
            case \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED:
                $collection->addFieldToFilter(
                    'second_table.status',
                    [\Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED]
                );
                break;
            case \Ess\M2ePro\Model\Amazon\Order::STATUS_INVOICE_UNCONFIRMED:
                $collection->addFieldToFilter(
                    'second_table.status',
                    [\Ess\M2ePro\Model\Amazon\Order::STATUS_INVOICE_UNCONFIRMED]
                );
                break;
            case \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING_RESERVED:
                $collection->addFieldToFilter(
                    'second_table.status',
                    [\Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING]
                );
                $collection->addFieldToFilter(
                    'reservation_state',
                    [\Ess\M2ePro\Model\Order\Reserve::STATE_PLACED]
                );
                break;
            case \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELLATION_REQUESTED:
                $collection->addFieldToFilter(
                    'second_table.status',
                    [\Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELLATION_REQUESTED]
                );
                break;
        }
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/amazon_order/grid', ['_current' => true]);
    }

    public function getRowUrl($item)
    {
        return false;
    }

    protected function _prepareLayout()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add("OrderObj.initializeGrids();");

            return parent::_toHtml();
        }

        $classConstants = $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Log\AbstractModel::class);
        $this->jsPhp->addConstants($classConstants);

        $this->jsUrl->addUrls([
            'amazon_order/view' => $this->getUrl(
                '*/amazon_order/view',
                ['back' => $this->dataHelper->makeBackUrlParam('*/amazon_order/index')]
            ),
            'getEditShippingAddressForm' => $this->getUrl(
                '*/amazon_order_shippingAddress/edit/'
            ),
        ]);
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon\Order'));
        $this->jsUrl->addUrls($this->dataHelper->getControllerActions('Amazon\Order\MerchantFulfillment'));

        $this->jsTranslator->addTranslations(
            [
                'View Full Order Log' => __('View Full Order Log'),
                'Amazon\'s Shipping Services' => __('Amazon\'s Shipping Services'),
                'Please select an option.' => __('Please select an option.'),
                'This is a required fields.' => __('This is a required fields.'),
                'Please enter a number greater than 0 in this fields.' =>
                    __('Please enter a number greater than 0 in this fields.'),
                'Are you sure you want to create Shipment now?' =>
                    __('Are you sure you want to create Shipment now?'),
                'Please enter a valid date.' => __('Please enter a valid date.'),
            ]
        );

        $tempGridIds = [];
        if ($this->amazonHelper->isEnabled()) {
            $tempGridIds[] = $this->getId();
        }
        $tempGridIds = \Ess\M2ePro\Helper\Json::encode($tempGridIds);

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Order',
        'M2ePro/Amazon/Order/MerchantFulfillment'
    ], function(){
        window.AmazonOrderMerchantFulfillmentObj = new AmazonOrderMerchantFulfillment();
        window.OrderObj = new Order('$tempGridIds');
        OrderObj.initializeGrids();
    });
JS
        );

        return parent::_prepareLayout();
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

    private function addInvoiceSentFilter(): void
    {
        $options = $this->advancedFilterAllOrdersOptions->getYesNoOptions();

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Order\Collection $orders,
            string $filterValue
        ): void {
            $orders->addFieldToFilter('is_invoice_sent', ['eq' => $filterValue]);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'is_invoice_sent',
            __('Invoice sent'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addCreditMemoSentFilter(): void
    {
        $options = $this->advancedFilterAllOrdersOptions->getYesNoOptions();

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Order\Collection $orders,
            string $filterValue
        ): void {
            $orders->addFieldToFilter('is_credit_memo_sent', ['eq' => $filterValue]);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'is_credit_memo_sent',
            __('Credit memo sent'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addPrimeFilter(): void
    {
        $options = $this->advancedFilterAllOrdersOptions->getYesNoOptions();

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Order\Collection $orders,
            string $filterValue
        ): void {
            $orders->addFieldToFilter('is_prime', ['eq' => $filterValue]);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'is_prime',
            __('Prime'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addB2BFilter(): void
    {
        $options = $this->advancedFilterAllOrdersOptions->getYesNoOptions();

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Order\Collection $orders,
            string $filterValue
        ): void {
            $orders->addFieldToFilter('is_business', ['eq' => $filterValue]);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'is_business',
            __('B2B'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }

    private function addInvoiceByAmazonFilter(): void
    {
        $options = $this->advancedFilterAllOrdersOptions->getYesNoOptions();

        $filterCallback = function (
            \Ess\M2ePro\Model\ResourceModel\Order\Collection $orders,
            string $filterValue
        ): void {
            $orders->addFieldToFilter('is_sold_by_amazon', ['eq' => $filterValue]);
        };

        $filter = $this->advancedFilterFactory->createDropDownFilter(
            'is_sold_by_amazon',
            __('Invoice by Amazon'),
            $options,
            $filterCallback
        );

        $this->addAdvancedFilter($filter);
    }
}
