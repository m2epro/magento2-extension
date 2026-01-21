<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;
use Ess\M2ePro\Model\Ebay\Order as EbayOrder;

class Grid extends AbstractGrid
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Note\Collection */
    protected $notesCollection;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    protected $ebayFactory;

    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Item\Collection */
    private $itemsCollection;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayHelper;
    /** @var \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\FilterFactory */
    private $advancedFilterFactory;
    /** @var \Ess\M2ePro\Model\Ebay\AdvancedFilter\AllOrdersOptions */
    private $advancedFilterAllOrdersOptions;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Ebay $ebayHelper,
        \Ess\M2ePro\Block\Adminhtml\Widget\Grid\AdvancedFilter\FilterFactory $advancedFilterFactory,
        \Ess\M2ePro\Model\Ebay\AdvancedFilter\AllOrdersOptions $advancedFilterAllOrdersOptions,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->ebayFactory = $ebayFactory;
        $this->databaseHelper = $databaseHelper;
        $this->dataHelper = $dataHelper;
        $this->ebayHelper = $ebayHelper;
        $this->advancedFilterFactory = $advancedFilterFactory;
        $this->advancedFilterAllOrdersOptions = $advancedFilterAllOrdersOptions;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayOrderGrid');
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
    }

    protected function _prepareCollection()
    {
        $collection = $this->ebayFactory->getObject('Order')->getCollection();

        $collection->getSelect()
                   ->joinLeft(
                       ['mea' => $this->activeRecordFactory->getObject('Ebay\Account')->getResource()->getMainTable()],
                       '(mea.account_id = `main_table`.account_id)',
                       ['account_mode' => 'mode']
                   )
                   ->joinLeft(
                       ['so' => $this->databaseHelper->getTableNameWithPrefix('sales_order')],
                       '(so.entity_id = `main_table`.magento_order_id)',
                       ['magento_order_num' => 'increment_id']
                   );

        // Add Order Status column
        // ---------------------------------------
        $shippingCompleted = \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED;
        $paymentCompleted = \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED;
        $returnedCompleted = \Ess\M2ePro\Model\Ebay\Order::BUYER_RETURN_REQUESTED_STATUS_APPROVED;

        $statusList = [
            'pending' => \Ess\M2ePro\Model\Ebay\Order::STATUS_PENDING,
            'unshipped' => \Ess\M2ePro\Model\Ebay\Order::STATUS_UNSHIPPED,
            'shipped' => \Ess\M2ePro\Model\Ebay\Order::STATUS_SHIPPED,
            'canceled' => \Ess\M2ePro\Model\Ebay\Order::STATUS_CANCELED,
            'returned' => \Ess\M2ePro\Model\Ebay\Order::STATUS_RETURNED,
        ];

        $isFullRefundedColumnName = \Ess\M2ePro\Model\ResourceModel\Ebay\Order::COLUMN_IS_FULL_REFUNDED;
        $collection->getSelect()->columns(
            [
                'status' => new \Zend_Db_Expr(
                    "IF (
                `cancellation_status` = 1 OR `$isFullRefundedColumnName` = 1,
                {$statusList['canceled']},
                IF (
                    `buyer_return_requested` = $returnedCompleted,
                    {$statusList['returned']},
                    IF (
                        `shipping_status` = $shippingCompleted,
                        {$statusList['shipped']},
                        IF (
                            `payment_status` = $paymentCompleted,
                            {$statusList['unshipped']},
                            {$statusList['pending']}
                        )
                    )
                )
            )"
                ),
            ]
        );

        // ---------------------------------------

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        $this->itemsCollection = $this->ebayFactory->getObject('Order\Item')
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
                'header' => $this->__('Sale Date'),
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
                'header' => $this->__('Ship By Date'),
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
                'header' => $this->__('Magento Order #'),
                'align' => 'left',
                'index' => 'so.increment_id',
                'width' => '200px',
                'frame_callback' => [$this, 'callbackColumnMagentoOrder'],
            ]
        );

        $this->addColumn(
            'ebay_order_id',
            [
                'header' => $this->__('eBay Order #'),
                'align' => 'left',
                'width' => '145px',
                'index' => 'ebay_order_id',
                'frame_callback' => [$this, 'callbackColumnEbayOrder'],
                'filter' => \Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\OrderId::class,
                'filter_condition_callback' => [$this, 'callbackFilterEbayOrderId'],
            ]
        );

        $this->addColumn(
            'ebay_order_items',
            [
                'header' => $this->__('Items'),
                'align' => 'left',
                'index' => 'ebay_order_items',
                'sortable' => false,
                'width' => '*',
                'frame_callback' => [$this, 'callbackColumnItems'],
                'filter_condition_callback' => [$this, 'callbackFilterItems'],
            ]
        );

        $this->addColumn(
            'buyer',
            [
                'header' => $this->__('Buyer'),
                'align' => 'left',
                'index' => 'buyer_user_id',
                'frame_callback' => [$this, 'callbackColumnBuyer'],
                'filter_condition_callback' => [$this, 'callbackFilterBuyer'],
                'width' => '120px',
            ]
        );

        $this->addColumn(
            'paid_amount',
            [
                'header' => $this->__('Total Paid'),
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
                'header' => $this->__('Status'),
                'align' => 'left',
                'width' => '50px',
                'index' => 'status',
                'type' => 'options',
                'options' => [
                    \Ess\M2ePro\Model\Ebay\Order::STATUS_PENDING => $this->__('Pending'),
                    \Ess\M2ePro\Model\Ebay\Order::STATUS_PENDING_RESERVED => $this->__('Pending / QTY Reserved'),
                    \Ess\M2ePro\Model\Ebay\Order::STATUS_UNSHIPPED => $this->__('Unshipped'),
                    \Ess\M2ePro\Model\Ebay\Order::STATUS_SHIPPED => $this->__('Shipped'),
                    \Ess\M2ePro\Model\Ebay\Order::STATUS_CANCELED => $this->__('Canceled'),
                    \Ess\M2ePro\Model\Ebay\Order::STATUS_RETURNED => $this->__('Returned'),
                ],
                'frame_callback' => [$this, 'callbackColumnStatus'],
                'filter_condition_callback' => [$this, 'callbackFilterStatus'],
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

        $groups = [
            'general' => __('General'),
            'order_cancellation' => __('Order Cancellation'),
            'order_return' => __('Order Return'),
        ];

        $this->getMassactionBlock()->setGroups($groups);

        // Set mass-action
        // ---------------------------------------
        $this->getMassactionBlock()->addItem(
            'reservation_place',
            [
                'label' => $this->__('Reserve QTY'),
                'url' => $this->getUrl('*/order/reservationPlace'),
                'confirm' => $this->__('Are you sure?'),
            ],
            'general'
        );

        $this->getMassactionBlock()->addItem(
            'reservation_cancel',
            [
                'label' => $this->__('Cancel QTY Reserve'),
                'url' => $this->getUrl('*/order/reservationCancel'),
                'confirm' => $this->__('Are you sure?'),
            ],
            'general'
        );

        $this->getMassactionBlock()->addItem(
            'ship',
            [
                'label' => $this->__('Mark Order(s) as Shipped'),
                'url' => $this->getUrl('*/ebay_order/updateShippingStatus'),
                'confirm' => $this->__('Are you sure?'),
            ],
            'general'
        );

        $this->getMassactionBlock()->addItem(
            'pay',
            [
                'label' => $this->__('Mark Order(s) as Paid'),
                'url' => $this->getUrl('*/ebay_order/updatePaymentStatus'),
                'confirm' => $this->__('Are you sure?'),
            ],
            'general'
        );

        $this->getMassactionBlock()->addItem(
            'resend_shipping',
            [
                'label' => $this->__('Resend Shipping Information'),
                'url' => $this->getUrl('*/order/resubmitShippingInfo'),
                'confirm' => $this->__('Are you sure?'),
            ],
            'general'
        );

        $this->getMassactionBlock()->addItem(
            'create_order',
            [
                'label' => $this->__('Create Magento Order'),
                'url' => $this->getUrl('*/ebay_order/CreateMagentoOrder'),
                'confirm' => $this->__('Are you sure?'),
            ],
            'general'
        );

        $this->getMassactionBlock()->addItem(
            'approve_cancel_by_buyer',
            [
                'label' => $this->__('Accept cancellation request'),
                'url' => $this->getUrl(
                    '*/ebay_order_processBuyerCancellationRequest/approve'
                ),
                'confirm' => $this->__('Are you sure?'),
            ],
            'order_cancellation'
        );

        $this->getMassactionBlock()->addItem(
            'reject_cancel_by_buyer',
            [
                'label' => $this->__('Decline cancellation request'),
                'url' => $this->getUrl(
                    '*/ebay_order_processBuyerCancellationRequest/reject'
                ),
                'confirm' => $this->__('Are you sure?'),
            ],
            'order_cancellation'
        );

        $this->getMassactionBlock()->addItem(
            'approve_return_by_buyer',
            [
                'label' => $this->__('Approve return request'),
                'url' => $this->getUrl(
                    '*/ebay_order_processBuyerReturnRequest/approve'
                ),
                'confirm' => $this->__('Are you sure?'),
            ],
            'order_return'
        );

        $this->getMassactionBlock()->addItem(
            'decline_return_by_buyer',
            [
                'label' => $this->__('Decline return request'),
                'url' => $this->getUrl(
                    '*/ebay_order_processBuyerReturnRequest/decline'
                ),
                'confirm' => $this->__('Are you sure?'),
            ],
            'order_return'
        );

        return parent::_prepareMassaction();
    }

    public function callbackColumnMagentoOrder($value, $row, $column, $isExport)
    {
        $magentoOrderId = $row['magento_order_id'];
        $returnString = $this->__('N/A');

        if ($magentoOrderId !== null) {
            if ($row['magento_order_num']) {
                $magentoOrderNumber = $this->dataHelper->escapeHtml($row['magento_order_num'] ?? '');
                $orderUrl = $this->getUrl('sales/order/view', ['order_id' => $magentoOrderId]);
                $returnString = '<a href="' . $orderUrl . '" target="_blank">' . $magentoOrderNumber . '</a>';
            } else {
                $returnString = '<span style="color: red;">' . $this->__('Deleted') . '</span>';
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

    public function callbackPurchaseCreateDate($value, $row, $column, $isExport)
    {
        $purchaseDate = $row->getChildObject()->getData('purchase_create_date');
        if (empty($purchaseDate)) {
            return '';
        }

        return $this->_localeDate->formatDate(
            $purchaseDate,
            \IntlDateFormatter::MEDIUM,
            true
        );
    }

    public function callbackShippingDateTo($value, $row, $column, $isExport)
    {
        $shippingDate = $row->getChildObject()->getData('shipping_date_to');
        if (empty($shippingDate)) {
            return '';
        }

        return $this->_localeDate->formatDate(
            $shippingDate,
            \IntlDateFormatter::MEDIUM,
            true
        );
    }

    public function callbackColumnEbayOrder($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Model\Order $row */

        $back = $this->dataHelper->makeBackUrlParam('*/ebay_order/index');
        $itemUrl = $this->getUrl('*/ebay_order/view', ['id' => $row->getId(), 'back' => $back]);

        $returnString = <<<HTML
<a href="{$itemUrl}">{$row->getChildObject()->getData('ebay_order_id')}</a>
HTML;

        if ($row->getChildObject()->getData('selling_manager_id')) {
            $returnString .= '<br/> [ <b>SM: </b> # ' . $row->getChildObject()->getData('selling_manager_id') . ' ]';
        }

        if (
            $row->getChildObject()->isBuyerReturnRequested()
            && $row->getChildObject()->isReturnRequestedProcessPossible()
        ) {
            $translation = __('Return requested');
            $returnString .= <<<HTML
<br/> <span style="color: red;">{$translation}</span><br/>
HTML;
        }

        /** @var \Ess\M2ePro\Model\Order\Note[] $notes */
        $notes = $this->notesCollection->getItemsByColumnValue('order_id', $row->getData('id'));
        $returnString .= $this->formatNotes($notes);

        if (empty($row->getChildObject()->getData('shipping_details'))) {
            return $returnString;
        }

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
            <div class="ebay-identifiers">
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

    public function callbackColumnItems($value, $row, $column, $isExport)
    {
        /** @var \Ess\M2ePro\Model\Order\Item[] $items */
        $items = $this->itemsCollection->getItemsByColumnValue('order_id', $row->getData('id'));

        $html = '';
        $gridId = $this->getId();

        if (!empty($items[0])) {
            $ebayOrder = $items[0]->getOrder()->getChildObject();
            if (
                $ebayOrder->getBuyerCancellationStatus() === EbayOrder::BUYER_CANCELLATION_STATUS_REQUESTED
                && $ebayOrder->isBuyerCancellationPossible()
            ) {
                $translation = __('Cancellation Requested');
                $html .= <<<HTML
<span style="color: red;">{$translation}</span><br/>
HTML;
            }
        }

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
                $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

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
                $orderItemEditLabel = $this->__('edit');

                $js = "{OrderEditItemObj.edit('{$gridId}', {$orderItemId});}";

                $editItemHtml = <<<HTML
<span>&nbsp;<a href="javascript:void(0);" onclick="{$js}">[{$orderItemEditLabel}]</a></span>
HTML;
            }

            $skuHtml = '';
            if ($item->getChildObject()->getSku()) {
                $skuLabel = $this->__('SKU');
                $sku = $this->dataHelper->escapeHtml($item->getChildObject()->getSku());
                if ($product !== null) {
                    $productUrl = $this->getUrl('catalog/product/edit', ['id' => $product->getId()]);
                    $sku = <<<STRING
<a href="{$productUrl}" target="_blank">{$sku}</a>
STRING;
                }

                $skuHtml = <<<HTML
<span style="padding-left: 10px;"><b>{$skuLabel}:</b>&nbsp;{$sku}</span><br/>
HTML;
            }

            $variation = $item->getChildObject()->getVariationOptions();
            $variationHtml = '';

            if (!empty($variation)) {
                $optionsLabel = $this->__('Options');

                $additionalHtml = '';
                if ($isShowEditLink) {
                    $additionalHtml = $editItemHtml;
                }

                $variationHtml .= <<<HTML
<span style="padding-left: 10px;"><b>{$optionsLabel}:</b>{$additionalHtml}</span><br/>
HTML;

                foreach ($variation as $optionName => $optionValue) {
                    $optionName = $this->dataHelper->escapeHtml($optionName);
                    $optionValue = $this->dataHelper->escapeHtml($optionValue);

                    $variationHtml .= <<<HTML
<span style="padding-left: 20px;"><b><i>{$optionName}</i>:</b>&nbsp;{$optionValue}</span><br/>
HTML;
                }
            }

            $qtyLabel = $this->__('QTY');
            $qty = (int)$item->getChildObject()->getQtyPurchased();

            $transactionHtml = <<<HTML
<span style="padding-left: 10px;"><b>{$qtyLabel}:</b>&nbsp;{$qty}</span><br/>
HTML;

            if ($item->getChildObject()->getTransactionId()) {
                $transactionLabel = $this->__('Transaction');
                $transactionId = $this->dataHelper->escapeHtml($item->getChildObject()->getTransactionId());

                $transactionHtml .= <<<HTML
<span style="padding-left: 10px;"><b>{$transactionLabel}:</b>&nbsp;{$transactionId}</span>
HTML;
            }

            $itemUrl = $this->ebayHelper->getItemUrl(
                $item->getChildObject()->getItemId(),
                $row->getData('account_mode'),
                (int)$row->getData('marketplace_id')
            );
            $itemLabel = $this->__('Item');
            $itemId = $this->dataHelper->escapeHtml($item->getChildObject()->getItemId());
            $itemTitle = $this->dataHelper->escapeHtml($item->getChildObject()->getTitle());

            $html .= <<<HTML
<b>{$itemLabel}: #</b> <a href="{$itemUrl}" target="_blank">{$itemId}</a><br/>
{$itemTitle}<br/>
<small>{$skuHtml}{$variationHtml}{$transactionHtml}</small>
HTML;
        }

        return $html;
    }

    public function callbackColumnBuyer($value, $row, $column, $isExport)
    {
        $returnString = $this->dataHelper->escapeHtml($row->getChildObject()->getData('buyer_name')) . '<br/>';
        $returnString .= $this->dataHelper->escapeHtml($row->getChildObject()->getData('buyer_user_id'));

        return $returnString;
    }

    public function callbackColumnTotal($value, $row, $column, $isExport)
    {
        return $this->modelFactory->getObject('Currency')->formatPrice(
            $row->getChildObject()->getData('currency'),
            $row->getChildObject()->getData('paid_amount')
        );
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $status = $row->getData('status');

        $statusColors = [
            \Ess\M2ePro\Model\Ebay\Order::STATUS_PENDING => 'gray',
            \Ess\M2ePro\Model\Ebay\Order::STATUS_SHIPPED => 'green',
            \Ess\M2ePro\Model\Ebay\Order::STATUS_CANCELED => 'red',
        ];

        $color = isset($statusColors[$status]) ? $statusColors[$status] : 'black';
        $value = '<span style="color: ' . $color . ';">' . $value . '</span>';

        return $value;
    }

    protected function callbackFilterEbayOrderId($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if (empty($value)) {
            return;
        }

        if (!empty($value['value'])) {
            $collection
                ->getSelect()
                ->where('ebay_order_id LIKE ? OR selling_manager_id LIKE ?', '%' . $value['value'] . '%');
        }
    }

    protected function callbackFilterItems($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $orderItemsCollection = $this->ebayFactory->getObject('Order\Item')->getCollection();

        $orderItemsCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $orderItemsCollection->getSelect()->columns('order_id');
        $orderItemsCollection->getSelect()->distinct(true);

        $orderItemsCollection
            ->getSelect()
            ->where('item_id LIKE ? OR title LIKE ? OR sku LIKE ? OR transaction_id LIKE ?', '%' . $value . '%');

        $ordersIds = $orderItemsCollection->getColumnValues('order_id');
        $collection->addFieldToFilter('main_table.id', ['in' => $ordersIds]);
    }

    protected function callbackFilterBuyer($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection
            ->getSelect()
            ->where(
                'second_table.buyer_email LIKE ?
                OR second_table.buyer_user_id LIKE ?
                OR second_table.buyer_name LIKE ?',
                '%' . $value . '%'
            );
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        if ($value == \Ess\M2ePro\Model\Ebay\Order::STATUS_CANCELED) {
            $collection->addFieldToFilter('cancellation_status', 1);

            return;
        }

        $collection->addFieldToFilter('cancellation_status', 0);
        switch ($value) {
            case \Ess\M2ePro\Model\Ebay\Order::STATUS_SHIPPED:
                $collection->addFieldToFilter(
                    'shipping_status',
                    \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED
                );
                break;

            case \Ess\M2ePro\Model\Ebay\Order::STATUS_UNSHIPPED:
                $collection->addFieldToFilter(
                    'payment_status',
                    \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED
                );
                $collection->addFieldToFilter(
                    'shipping_status',
                    ['neq' => \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED]
                );
                break;

            case \Ess\M2ePro\Model\Ebay\Order::STATUS_PENDING:
                $collection->addFieldToFilter(
                    'payment_status',
                    ['neq' => \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED]
                );
                $collection->addFieldToFilter(
                    'shipping_status',
                    ['neq' => \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED]
                );
                break;
            case \Ess\M2ePro\Model\Ebay\Order::STATUS_PENDING_RESERVED:
                $collection->addFieldToFilter(
                    'payment_status',
                    ['neq' => \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED]
                );
                $collection->addFieldToFilter(
                    'reservation_state',
                    [\Ess\M2ePro\Model\Order\Reserve::STATE_PLACED]
                );
                break;
            case \Ess\M2ePro\Model\Ebay\Order::STATUS_RETURNED:
                $collection->addFieldToFilter(
                    'buyer_return_requested',
                    \Ess\M2ePro\Model\Ebay\Order::BUYER_RETURN_REQUESTED_STATUS_APPROVED
                );
                break;
        }
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_order/grid', ['_current' => true]);
    }

    public function getRowUrl($item)
    {
        return false;
    }

    protected function _toHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add("OrderObj.initializeGrids();");

            return parent::_toHtml();
        }

        $classConstants = $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Log\AbstractModel::class);
        $this->jsPhp->addConstants($classConstants);

        $this->jsUrl->addUrls([
            'ebay_order/view' => $this->getUrl(
                '*/ebay_order/view',
                ['back' => $this->dataHelper->makeBackUrlParam('*/ebay_order/index')]
            ),
        ]);

        $this->jsTranslator->add('View Full Order Log', $this->__('View Full Order Log'));

        $tempGridIds = [];
        if ($this->ebayHelper->isEnabled()) {
            $tempGridIds[] = $this->getId();
        }
        $tempGridIds = \Ess\M2ePro\Helper\Json::encode($tempGridIds);

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Order'
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

            $orders->addFieldToFilter('main_table.account_id', ['eq' => (int)$filterValue]);
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
}
