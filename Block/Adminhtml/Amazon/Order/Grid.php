<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;
use Ess\M2ePro\Model\Amazon\Listing\Product;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Order\Grid
 */
class Grid extends AbstractGrid
{
    /** @var $itemsCollection \Ess\M2ePro\Model\ResourceModel\Order\Item\Collection */
    private $itemsCollection = null;

    /** @var $notesCollection \Ess\M2ePro\Model\ResourceModel\Order\Note\Collection */
    protected $notesCollection = null;

    protected $resourceConnection;
    protected $amazonFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->amazonFactory = $amazonFactory;
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
        return 'Ess\M2ePro\Block\Adminhtml\Magento\Grid\Massaction';
    }

    protected function _prepareCollection()
    {
        $collection = $this->amazonFactory->getObject('Order')->getCollection();

        $collection->getSelect()
            ->joinLeft(
                ['so' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('sales_order')],
                '(so.entity_id = `main_table`.magento_order_id)',
                ['magento_order_num' => 'increment_id']
            );

        // Add Filter By Account
        // ---------------------------------------
        if ($accountId = $this->getRequest()->getParam('amazonAccount')) {
            $collection->addFieldToFilter('main_table.account_id', $accountId);
        }
        // ---------------------------------------

        // Add Filter By Marketplace
        // ---------------------------------------
        if ($marketplaceId = $this->getRequest()->getParam('amazonMarketplace')) {
            $collection->addFieldToFilter('main_table.marketplace_id', $marketplaceId);
        }
        // ---------------------------------------

        // Add Not Created Magento Orders Filter
        // ---------------------------------------
        if ($this->getRequest()->getParam('not_created_only')) {
            $collection->addFieldToFilter('magento_order_id', ['null' => true]);
        }
        // ---------------------------------------

        // Add Not sent Invoice or Credit Memo Filter
        // ---------------------------------------
        if ($this->getRequest()->getParam('invoice_or_creditmemo_not_sent')) {
            $collection->addFieldToFilter('is_invoice_sent', 0);
            $collection->addFieldToFilter('is_credit_memo_sent', 0);
        }

        // ---------------------------------------

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        $this->itemsCollection = $this->amazonFactory->getObject('Order\Item')
            ->getCollection()
            ->addFieldToFilter('order_id', ['in' => $this->getCollection()->getColumnValues('id')]);

        $this->notesCollection = $this->activeRecordFactory->getObject('Order\Note')
            ->getCollection()
            ->addFieldToFilter('order_id', ['in' => $this->getCollection()->getColumnValues('id')]);

        return parent::_afterLoadCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'purchase_create_date',
            [
                'header'         => $this->__('Sale Date'),
                'align'          => 'left',
                'type'           => 'datetime',
                'filter'         => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime',
                'format'         => \IntlDateFormatter::MEDIUM,
                'filter_time'    => true,
                'index'          => 'purchase_create_date',
                'width'          => '170px',
                'frame_callback' => [$this, 'callbackPurchaseCreateDate']
            ]
        );

        $this->addColumn(
            'shipping_date_to',
            [
                'header'         => $this->__('Ship By Date'),
                'align'          => 'left',
                'type'           => 'datetime',
                'filter'         => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime',
                'format'         => \IntlDateFormatter::MEDIUM,
                'filter_time'    => true,
                'index'          => 'shipping_date_to',
                'width'          => '170px',
                'frame_callback' => [$this, 'callbackShippingDateTo'],
            ]
        );

        $this->addColumn(
            'magento_order_num',
            [
                'header'         => $this->__('Magento Order #'),
                'align'          => 'left',
                'index'          => 'so.increment_id',
                'width'          => '110px',
                'frame_callback' => [$this, 'callbackColumnMagentoOrder']
            ]
        );

        $this->addColumn(
            'amazon_order_id',
            [
                'header'         => $this->__('Amazon Order #'),
                'align'          => 'left',
                'width'          => '160px',
                'index'          => 'amazon_order_id',
                'frame_callback' => [$this, 'callbackColumnAmazonOrderId']
            ]
        );

        $this->addColumn(
            'amazon_order_items',
            [
                'header'                    => $this->__('Items'),
                'align'                     => 'left',
                'index'                     => 'amazon_order_items',
                'sortable'                  => false,
                'width'                     => '*',
                'frame_callback'            => [$this, 'callbackColumnItems'],
                'filter_condition_callback' => [$this, 'callbackFilterItems']
            ]
        );

        $this->addColumn(
            'buyer',
            [
                'header'                    => $this->__('Buyer'),
                'align'                     => 'left',
                'index'                     => 'buyer_name',
                'width'                     => '120px',
                'frame_callback'            => [$this, 'callbackColumnBuyer'],
                'filter_condition_callback' => [$this, 'callbackFilterBuyer']
            ]
        );

        $this->addColumn(
            'paid_amount',
            [
                'header'         => $this->__('Total Paid'),
                'align'          => 'left',
                'width'          => '110px',
                'index'          => 'paid_amount',
                'type'           => 'number',
                'frame_callback' => [$this, 'callbackColumnTotal']
            ]
        );

        $this->addColumn(
            'is_afn_channel',
            [
                'header'         => $this->__('Fulfillment'),
                'width'          => '100px',
                'index'          => 'is_afn_channel',
                'filter_index'   => 'second_table.is_afn_channel',
                'type'           => 'options',
                'sortable'       => false,
                'options'        => [
                    0 => $this->__('Merchant'),
                    1 => $this->__('Amazon')
                ],
                'frame_callback' => [$this, 'callbackColumnAfnChannel']
            ]
        );

        $this->addColumn(
            'status',
            [
                'header'         => $this->__('Status'),
                'align'          => 'left',
                'width'          => '50px',
                'index'          => 'status',
                'filter_index'   => 'second_table.status',
                'type'           => 'options',
                'options'        => [
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING             => $this->__('Pending'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING_RESERVED    => $this->__('Pending / QTY Reserved'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED           => $this->__('Unshipped'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED_PARTIALLY   => $this->__('Partially Shipped'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED             => $this->__('Shipped'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_INVOICE_UNCONFIRMED => $this->__('Invoice Not Confirmed'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_UNFULFILLABLE       => $this->__('Unfulfillable'),
                    \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED            => $this->__('Canceled')
                ],
                'frame_callback' => [$this, 'callbackColumnStatus'],
                'filter_condition_callback' => [$this, 'callbackFilterStatus']
            ]
        );

        $this->addColumn(
            'actions',
            [
                'header'   => $this->__('Actions'),
                'align'    => 'left',
                'width'    => '100px',
                'type'     => 'action',
                'index'    => 'actions',
                'filter'   => false,
                'sortable' => false,
                'renderer' => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
                'getter'   => 'getId',
                'actions'  => [
                    [
                        'caption' => $this->__('View'),
                        'url'     => [
                            'base' => '*/amazon_order/view'
                        ],
                        'field'   => 'id'
                    ],
                    [
                        'caption' => $this->__('Create Magento Order'),
                        'url'     => [
                            'base' => '*/amazon_order/createMagentoOrder',
                        ],
                        'field'   => 'id'
                    ],
                    [
                        'caption'        => $this->__('Mark As Shipped'),
                        'field'          => 'id',
                        'onclick_action' => 'AmazonOrderMerchantFulfillmentObj.markAsShippedAction'
                    ],
                    [
                        'caption'        => $this->__('Amazon\'s Shipping Services'),
                        'field'          => 'id',
                        'onclick_action' => 'AmazonOrderMerchantFulfillmentObj.getPopupAction'
                    ]
                ]
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
                'label'   => $this->__('Reserve QTY'),
                'url'     => $this->getUrl('*/order/reservationPlace'),
                'confirm' => $this->__('Are you sure?')
            ]
        );

        $this->getMassactionBlock()->addItem(
            'reservation_cancel',
            [
                'label'   => $this->__('Cancel QTY Reserve'),
                'url'     => $this->getUrl('*/order/reservationCancel'),
                'confirm' => $this->__('Are you sure?')
            ]
        );

        $this->getMassactionBlock()->addItem(
            'ship',
            [
                'label'   => $this->__('Mark Order(s) as Shipped'),
                'url'     => $this->getUrl('*/amazon_order/updateShippingStatus'),
                'confirm' => $this->__('Are you sure?')
            ]
        );

        $this->getMassactionBlock()->addItem(
            'resend_shipping',
            [
                'label'   => $this->__('Resend Shipping Information'),
                'url'     => $this->getUrl('*/order/resubmitShippingInfo'),
                'confirm' => $this->__('Are you sure?')
            ]
        );

        $this->getMassactionBlock()->addItem(
            'resend_invoice_creditmemo',
            [
                'label'   => $this->__('Resend Invoice / Credit Memo'),
                'url'     => $this->getUrl('*/amazon_order/resendInvoiceCreditmemo'),
                'confirm' => $this->__('Are you sure?')
            ]
        );

        $this->getMassactionBlock()->addItem(
            'create_order',
            [
                'label'   => $this->__('Create Magento Order'),
                'url'     => $this->getUrl('*/amazon_order/createMagentoOrder'),
                'confirm' => $this->__('Are you sure?')
            ]
        );

        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

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

    public function callbackColumnAmazonOrderId($value, $row, $column, $isExport)
    {
        $back = $this->getHelper('Data')->makeBackUrlParam('*/amazon_order/index');
        $itemUrl = $this->getUrl('*/amazon_order/view', ['id' => $row->getId(), 'back' => $back]);

        $orderId = $this->getHelper('Data')->escapeHtml($row->getChildObject()->getData('amazon_order_id'));
        $url = $this->getHelper('Component\Amazon')->getOrderUrl($orderId, $row->getData('marketplace_id'));

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

        $returnString .= <<<HTML
<a title="{$this->__('View on Amazon')}" target="_blank" href="{$url}">
<img style="margin-bottom: -3px; float: right"
 src="{$this->getViewFileUrl('Ess_M2ePro::images/view_amazon.png')}" alt="{$this->__('View on Amazon')}" /></a>
HTML;
        $returnString .= $primeImageHtml;
        $returnString .= $businessImageHtml;

        /** @var $notes \Ess\M2ePro\Model\Order\Note[] */
        $notes = $this->notesCollection->getItemsByColumnValue('order_id', $row->getData('id'));

        if ($notes) {
            $htmlNotesCount = $this->__(
                'You have a custom note for the order. It can be reviewed on the order detail page.'
            );

            $returnString .= <<<HTML
<div class="note_icon admin__field-tooltip">
    <a class="admin__field-tooltip-note-action" href="javascript://"></a>
    <div class="admin__field-tooltip-content" style="right: -4.4rem">
        <div class="amazon-identifiers">
           {$htmlNotesCount}
        </div>
    </div>
</div>
HTML;
        }

        return $returnString;
    }

    public function callbackColumnMagentoOrder($value, $row, $column, $isExport)
    {
        $magentoOrderId = $row['magento_order_id'];
        $magentoOrderNumber = $this->getHelper('Data')->escapeHtml($row['magento_order_num']);

        $returnString = $this->__('N/A');

        if ($magentoOrderId !== null) {
            if ($row['magento_order_num']) {
                $orderUrl = $this->getUrl('sales/order/view', ['order_id' => $magentoOrderId]);
                $returnString = '<a href="' . $orderUrl . '" target="_blank">' . $magentoOrderNumber . '</a>';
            } else {
                $returnString = '<span style="color: red;">' . $this->__('Deleted') . '</span>';
            }
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Grid\Column\Renderer\ViewLogIcon\Order $viewLogIcon */
        $viewLogIcon = $this->createBlock('Grid_Column_Renderer_ViewLogIcon_Order');
        $logIconHtml = $viewLogIcon->render($row);

        if ($logIconHtml !== '') {
            return '<div style="min-width: 100px">' . $returnString . $logIconHtml . '</div>';
        }

        return $returnString;
    }

    // ---------------------------------------

    public function callbackColumnItems($value, $row, $column, $isExport)
    {
        /** @var $items \Ess\M2ePro\Model\Order\Item[] */
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

                if ($magentoProduct->isProductWithVariations()
                    && empty($associatedOptions)
                    && empty($associatedProducts)
                ) {
                    $isShowEditLink = true;
                }
            }

            $editItemHtml = '';
            if ($isShowEditLink) {
                $orderItemId = $item->getId();
                $orderItemEditLabel = $this->__('edit');

                $js = "{OrderEditItemObj.edit('{$gridId}', {$orderItemId});}";

                $editItemHtml = <<<HTML
<span>&nbsp;<a href="javascript:void(0);" onclick="{$js}">[{$orderItemEditLabel}]</a></span>
HTML;
            }

            $skuHtml = '';
            if ($item->getChildObject()->getSku()) {
                $skuLabel = $this->__('SKU');
                $sku = $this->getHelper('Data')->escapeHtml($item->getChildObject()->getSku());
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

            $generalIdLabel = $this->__($item->getChildObject()->getIsIsbnGeneralId() ? 'ISBN' : 'ASIN');
            $generalId = $this->getHelper('Data')->escapeHtml($item->getChildObject()->getGeneralId());

            $itemUrl = $this->getHelper('Component\Amazon')->getItemUrl(
                $item->getChildObject()->getGeneralId(),
                $row->getData('marketplace_id')
            );

            $itemLink = '<a href="' . $itemUrl . '" target="_blank">' . $generalId . '</a>';

            $generalIdHtml = <<<STRING
<span style="padding-left: 10px;"><b>{$generalIdLabel}:</b>&nbsp;{$itemLink}</span><br/>
STRING;

            $itemTitle = $this->getHelper('Data')->escapeHtml($item->getChildObject()->getTitle());
            $qtyLabel = $this->__('QTY');
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
            return $this->__('N/A');
        }

        return $this->getHelper('Data')->escapeHtml($row->getChildObject()->getData('buyer_name'));
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
        if ($row->getChildObject()->getData('is_afn_channel') == Product::IS_AFN_CHANNEL_YES
        ) {
            return '<span style="font-weight: bold;">' . $this->__('Amazon') . '</span>';
        }

        return $this->__('Merchant');
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $statuses = [
            \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING             => $this->__('Pending'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_UNSHIPPED           => $this->__('Unshipped'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED_PARTIALLY   => $this->__('Partially Shipped'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED             => $this->__('Shipped'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_INVOICE_UNCONFIRMED => $this->__('Invoice Not Confirmed'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_UNFULFILLABLE       => $this->__('Unfulfillable'),
            \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED            => $this->__('Canceled')
        ];
        $status = $row->getChildObject()->getData('status');

        $value = $statuses[$status];

        $statusColors = [
            \Ess\M2ePro\Model\Amazon\Order::STATUS_PENDING  => 'gray',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_SHIPPED  => 'green',
            \Ess\M2ePro\Model\Amazon\Order::STATUS_CANCELED => 'red'
        ];

        $color = isset($statusColors[$status]) ? $statusColors[$status] : 'black';
        $value = '<span style="color: ' . $color . ';">' . $value . '</span>';

        if ($row->isSetProcessingLock('update_order_status')) {
            $value .= '<br/>';
            $value .= '<span style="color: gray;">['
                . $this->__('Status Update in Progress...') . ']</span>';
        }

        return $value;
    }

    //########################################

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
        }
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/amazon_order/grid', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    protected function _prepareLayout()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
                OrderObj.initializeGrids();
JS
            );
        }

        $this->jsPhp->addConstants(
            $this->getHelper('Data')
                ->getClassConstants(\Ess\M2ePro\Model\Log\AbstractModel::class)
        );

        $this->jsUrl->addUrls(
            [
                'amazon_order/view'          => $this->getUrl(
                    '*/amazon_order/view',
                    ['back' => $this->getHelper('Data')->makeBackUrlParam('*/amazon_order/index')]
                ),
                'getEditShippingAddressForm' => $this->getUrl(
                    '*/amazon_order_shippingAddress/edit/'
                )
            ]
        );
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Order'));
        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Order\MerchantFulfillment'));

        $this->jsTranslator->addTranslations(
            [
                'View Full Order Log'                                  => $this->__('View Full Order Log'),
                'Amazon\'s Shipping Services'                          => $this->__('Amazon\'s Shipping Services'),
                'Please select an option.'                             => $this->__('Please select an option.'),
                'This is a required fields.'                           => $this->__('This is a required fields.'),
                'Please enter a number greater than 0 in this fields.' =>
                    $this->__('Please enter a number greater than 0 in this fields.'),
                'Are you sure you want to create Shipment now?'        =>
                    $this->__('Are you sure you want to create Shipment now?'),
                'Please enter a valid date.'                           => $this->__('Please enter a valid date.'),
            ]
        );

        $tempGridIds = [];
        $this->getHelper('Component\Amazon')->isEnabled() && $tempGridIds[] = $this->getId();

        $tempGridIds = $this->getHelper('Data')->jsonEncode($tempGridIds);

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

    //########################################
}
