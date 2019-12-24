<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Order\Grid
 */
class Grid extends AbstractGrid
{
    private $itemsCollection = null;

    protected $resourceConnection;
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->ebayFactory = $ebayFactory;

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
                ['so' => $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('sales_order')],
                '(so.entity_id = `main_table`.magento_order_id)',
                ['magento_order_num' => 'increment_id']
            );

        // Add Filter By Account
        // ---------------------------------------
        if ($accountId = $this->getRequest()->getParam('ebayAccount')) {
            $collection->addFieldToFilter('main_table.account_id', $accountId);
        }
        // ---------------------------------------

        // Add Filter By Marketplace
        // ---------------------------------------
        if ($marketplaceId = $this->getRequest()->getParam('ebayMarketplace')) {
            $collection->addFieldToFilter('main_table.marketplace_id', $marketplaceId);
        }
        // ---------------------------------------

        // Add Not Created Magento Orders Filter
        // ---------------------------------------
        if ($this->getRequest()->getParam('not_created_only')) {
            $collection->addFieldToFilter('magento_order_id', ['null' => true]);
        }
        // ---------------------------------------

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        $this->itemsCollection = $this->ebayFactory->getObject('Order\Item')->getCollection()
            ->addFieldToFilter('order_id', ['in' => $this->getCollection()->getColumnValues('id')]);

        return parent::_afterLoadCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('purchase_create_date', [
            'header' => $this->__('Sale Date'),
            'align'  => 'left',
            'type'   => 'datetime',
            'filter' => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime',
            'format' => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'  => 'purchase_create_date',
            'width'  => '170px',
            'frame_callback' => [$this, 'callbackPurchaseCreateDate'],
        ]);

        $this->addColumn('magento_order_num', [
            'header' => $this->__('Magento Order #'),
            'align'  => 'left',
            'index'  => 'so.increment_id',
            'width'  => '200px',
            'frame_callback' => [$this, 'callbackColumnMagentoOrder']
        ]);

        $this->addColumn('ebay_order_id', [
            'header' => $this->__('eBay Order #'),
            'align'  => 'left',
            'width'  => '145px',
            'index'  => 'ebay_order_id',
            'frame_callback' => [$this, 'callbackColumnEbayOrder'],
            'filter'   => 'Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\OrderId',
            'filter_condition_callback' => [$this, 'callbackFilterEbayOrderId']
        ]);

        $this->addColumn('ebay_order_items', [
            'header' => $this->__('Items'),
            'align'  => 'left',
            'index'  => 'ebay_order_items',
            'sortable' => false,
            'width'  => '*',
            'frame_callback' => [$this, 'callbackColumnItems'],
            'filter_condition_callback' => [$this, 'callbackFilterItems']
        ]);

        $this->addColumn('buyer', [
            'header' => $this->__('Buyer'),
            'align'  => 'left',
            'index'  => 'buyer_user_id',
            'frame_callback' => [$this, 'callbackColumnBuyer'],
            'filter_condition_callback' => [$this, 'callbackFilterBuyer'],
            'width'  => '120px'
        ]);

        $this->addColumn('paid_amount', [
            'header' => $this->__('Total Paid'),
            'align'  => 'left',
            'width'  => '110px',
            'index'  => 'paid_amount',
            'type'   => 'number',
            'frame_callback' => [$this, 'callbackColumnTotal']
        ]);

        $this->addColumn('reservation_state', [
            'header' => $this->__('Reservation'),
            'align'  => 'left',
            'width'  => '50px',
            'index'  => 'reservation_state',
            'type'   => 'options',
            'options' => [
                \Ess\M2ePro\Model\Order\Reserve::STATE_UNKNOWN  => $this->__('Not Reserved'),
                \Ess\M2ePro\Model\Order\Reserve::STATE_PLACED   => $this->__('Reserved'),
                \Ess\M2ePro\Model\Order\Reserve::STATE_RELEASED => $this->__('Released'),
                \Ess\M2ePro\Model\Order\Reserve::STATE_CANCELED => $this->__('Canceled'),
            ]
        ]);

        $this->addColumn('checkout_status', [
            'header' => $this->__('Checkout'),
            'align'  => 'left',
            'width'  => '50px',
            'index'  => 'checkout_status',
            'type'   => 'options',
            'options' => [
                \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_INCOMPLETE => $this->__('No'),
                \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_COMPLETED  => $this->__('Yes')
            ],
            'frame_callback' => [$this, 'callbackColumnCheckoutStatus']
        ]);

        $this->addColumn('payment_status', [
            'header' => $this->__('Paid'),
            'align'  => 'left',
            'width'  => '50px',
            'index'  => 'payment_status',
            'type'   => 'options',
            'options' => [
                0 => $this->__('No'),
                1 => $this->__('Yes')
            ],
            'frame_callback' => [$this, 'callbackColumnPayment'],
            'filter_condition_callback' => [$this, 'callbackFilterPaymentCondition']
        ]);

        $this->addColumn('shipping_status', [
            'header' => $this->__('Shipped'),
            'align'  => 'left',
            'width'  => '50px',
            'index'  => 'shipping_status',
            'type'   => 'options',
            'options' => [
                0 => $this->__('No'),
                1 => $this->__('Yes')
            ],
            'frame_callback' => [$this, 'callbackColumnShipping'],
            'filter_condition_callback' => [$this, 'callbackFilterShippingCondition']
        ]);

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
            'general' => $this->__('General'),
        ];

        if ($this->getHelper('Component_Ebay_PickupStore')->isFeatureEnabled()) {
            $groups['in_store_pickup'] = $this->__('In-Store Pickup');
        }

        $this->getMassactionBlock()->setGroups($groups);

        // Set mass-action
        // ---------------------------------------
        $this->getMassactionBlock()->addItem('reservation_place', [
            'label'    => $this->__('Reserve QTY'),
            'url'      => $this->getUrl('*/order/reservationPlace'),
            'confirm'  => $this->__('Are you sure?')
        ], 'general');

        $this->getMassactionBlock()->addItem('reservation_cancel', [
            'label'    => $this->__('Cancel QTY Reserve'),
            'url'      => $this->getUrl('*/order/reservationCancel'),
            'confirm'  => $this->__('Are you sure?')
        ], 'general');

        $this->getMassactionBlock()->addItem('ship', [
            'label'    => $this->__('Mark Order(s) as Shipped'),
            'url'      => $this->getUrl('*/ebay_order/updateShippingStatus'),
            'confirm'  => $this->__('Are you sure?')
        ], 'general');

        $this->getMassactionBlock()->addItem('pay', [
            'label'    => $this->__('Mark Order(s) as Paid'),
            'url'      => $this->getUrl('*/ebay_order/updatePaymentStatus'),
            'confirm'  => $this->__('Are you sure?')
        ], 'general');

        $this->getMassactionBlock()->addItem('resend_shipping', [
            'label'    => $this->__('Resend Shipping Information'),
            'url'      => $this->getUrl('*/order/resubmitShippingInfo'),
            'confirm'  => $this->__('Are you sure?')
        ], 'general');
        // ---------------------------------------

        if (!$this->getHelper('Component_Ebay_PickupStore')->isFeatureEnabled()) {
            return parent::_prepareMassaction();
        }

        $this->getMassactionBlock()->addItem('mark_as_ready_for_pickup', [
            'label'    => $this->__('Mark as Ready For Pickup'),
            'url'      => $this->getUrl('*/ebay_order/markAsReadyForPickup'),
            'confirm'  => $this->__('Are you sure?')
        ], 'in_store_pickup');

        $this->getMassactionBlock()->addItem('mark_as_picked_up', [
            'label'    => $this->__('Mark as Picked Up'),
            'url'      => $this->getUrl('*/ebay_order/markAsPickedUp'),
            'confirm'  => $this->__('Are you sure?')
        ], 'in_store_pickup');

        $this->getMassactionBlock()->addItem('mark_as_cancelled', [
            'label'    => $this->__('Mark as Cancelled'),
            'url'      => $this->getUrl('*/ebay_order/markAsCancelled'),
            'confirm'  => $this->__('Are you sure?')
        ], 'in_store_pickup');

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackColumnMagentoOrder($value, $row, $column, $isExport)
    {
        $magentoOrderId = $row['magento_order_id'];
        $magentoOrderNumber = $this->getHelper('Data')->escapeHtml($row['magento_order_num']);

        $returnString = $this->__('N/A');

        if ($row['magento_order_id']) {
            if ($row['magento_order_num']) {
                $orderUrl = $this->getUrl('sales/order/view', ['order_id' => $magentoOrderId]);
                $returnString = '<a href="' . $orderUrl . '" target="_blank">' . $magentoOrderNumber . '</a>';
            } else {
                $returnString = '<span style="color: red;">'.$this->__('Deleted').'</span>';
            }
        }

        $logIconHtml = $this->getViewLogIconHtml($row->getId());

        if ($logIconHtml !== '') {
            return '<div style="min-width: 100px">' . $returnString . $logIconHtml . '</div>';
        }

        return $returnString;
    }

    private function getViewLogIconHtml($orderId)
    {
        $orderId = (int)$orderId;

        // Prepare collection
        // ---------------------------------------
        $orderLogsCollection = $this->activeRecordFactory->getObject('Order\Log')->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->setOrder('id', 'DESC');
        $orderLogsCollection->getSelect()
            ->limit(\Ess\M2ePro\Block\Adminhtml\Log\Grid\LastActions::ACTIONS_COUNT);

        if (!$orderLogsCollection->getSize()) {
            return '';
        }
        // ---------------------------------------

        $summary = $this->createBlock('Order_Log_Grid_LastActions')->setData([
            'entity_id' => $orderId,
            'logs'      => $orderLogsCollection->getItems(),
            'view_help_handler' => 'OrderObj.viewOrderHelp',
            'hide_help_handler' => 'OrderObj.hideOrderHelp',
        ]);

        return $summary->toHtml();
    }

    // ---------------------------------------

    public function callbackPurchaseCreateDate($value, $row, $column, $isExport)
    {
        return $this->_localeDate->formatDate(
            $row->getChildObject()->getData('purchase_create_date'),
            \IntlDateFormatter::MEDIUM,
            true
        );
    }

    public function callbackColumnEbayOrder($value, $row, $column, $isExport)
    {
        $returnString = str_replace('-', '-<br/>', $row->getChildObject()->getData('ebay_order_id'));

        if ($row->getChildObject()->getData('selling_manager_id') > 0) {
            $returnString .= '<br/> [ <b>SM: </b> # ' . $row->getChildObject()->getData('selling_manager_id') . ' ]';
        }

        if (!$this->getHelper('Component_Ebay_PickupStore')->isFeatureEnabled()) {
            return $returnString;
        }

        if (empty($row->getChildObject()->getData('shipping_details'))) {
            return $returnString;
        }

        $shippingDetails = $this->getHelper('Data')->jsonDecode($row->getChildObject()->getData('shipping_details'));
        if (empty($shippingDetails['in_store_pickup_details'])) {
            return $returnString;
        }

        $returnString = '<img src="/images/in_store_pickup.png" />&nbsp;'.$returnString;

        return $returnString;
    }

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

            $product = $item->getProduct();
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
                    $optionName = $this->getHelper('Data')->escapeHtml($optionName);
                    $optionValue = $this->getHelper('Data')->escapeHtml($optionValue);

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
                $transactionId = $this->getHelper('Data')->escapeHtml($item->getChildObject()->getTransactionId());

                $transactionHtml .= <<<HTML
<span style="padding-left: 10px;"><b>{$transactionLabel}:</b>&nbsp;{$transactionId}</span>
HTML;
            }

            $itemUrl = $this->getHelper('Component\Ebay')->getItemUrl(
                $item->getChildObject()->getItemId(),
                $row->getData('account_mode'),
                (int)$row->getData('marketplace_id')
            );
            $itemLabel = $this->__('Item');
            $itemId = $this->getHelper('Data')->escapeHtml($item->getChildObject()->getItemId());
            $itemTitle = $this->getHelper('Data')->escapeHtml($item->getChildObject()->getTitle());

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
        $returnString = '';
        $returnString .= $this->getHelper('Data')->escapeHtml($row->getChildObject()->getData('buyer_name')) . '<br/>';

        $buyerEmail = $row->getChildObject()->getData('buyer_email');
        if ($buyerEmail && $buyerEmail != 'Invalid Request') {
            $returnString .= '&lt;' . $buyerEmail  . '&gt;<br/>';
        }

        $returnString .= $this->getHelper('Data')->escapeHtml($row->getChildObject()->getData('buyer_user_id'));

        return $returnString;
    }

    public function callbackColumnTotal($value, $row, $column, $isExport)
    {
        return $this->modelFactory->getObject('Currency')->formatPrice(
            $row->getChildObject()->getData('currency'),
            $row->getChildObject()->getData('paid_amount')
        );
    }

    public function callbackColumnShipping($value, $row, $column, $isExport)
    {
        if ($row->getChildObject()->getData('shipping_status')
                == \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED) {
            return $this->__('Yes');
        } else {
            return $this->__('No');
        }
    }

    public function callbackColumnCheckoutStatus($value, $row, $column, $isExport)
    {
        if ($row->getChildObject()->getData('checkout_status')
                == \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_COMPLETED) {
            return $this->__('Yes');
        } else {
            return $this->__('No');
        }
    }

    public function callbackColumnPayment($value, $row, $column, $isExport)
    {
        if ($row->getChildObject()->getData('payment_status')
                == \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED) {
            return $this->__('Yes');
        } else {
            return $this->__('No');
        }
    }

    //########################################

    protected function callbackFilterEbayOrderId($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if (empty($value)) {
            return;
        }

        if (!empty($value['value'])) {
            $collection
                ->getSelect()
                ->where('ebay_order_id LIKE ? OR selling_manager_id LIKE ?', '%'.$value['value'].'%');
        }

        if (!empty($value['is_in_store_pickup'])) {
            $collection->getSelect()->where(
                'shipping_details regexp ?',
                '"in_store_pickup_details":\{.+\}'
            );
        }
    }

    protected function callbackFilterItems($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $orderItemsCollection = $this->ebayFactory->getObject('Order\Item')->getCollection();

        $orderItemsCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $orderItemsCollection->getSelect()->columns('order_id');
        $orderItemsCollection->getSelect()->distinct(true);

        $orderItemsCollection
            ->getSelect()
            ->where('item_id LIKE ? OR title LIKE ? OR sku LIKE ? OR transaction_id LIKE ?', '%'.$value.'%');

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
            ->where('buyer_email LIKE ? OR buyer_user_id LIKE ? OR buyer_name LIKE ?', '%'.$value.'%');
    }

    protected function callbackFilterPaymentCondition($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value === null) {
            return;
        }
        $filterType = ($value == 1) ? 'eq' : 'neq';
        $this->getCollection()->addFieldToFilter(
            'payment_status',
            [$filterType => \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED]
        );
    }

    protected function callbackFilterShippingCondition($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value === null) {
            return;
        }
        $filterType = ($value == 1) ? 'eq' : 'neq';
        $this->getCollection()->addFieldToFilter(
            'shipping_status',
            [$filterType => \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED]
        );
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_order/grid', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        $back = $this->getHelper('Data')->makeBackUrlParam(
            '*/ebay_order/index'
        );

        return $this->getUrl('*/ebay_order/view', ['id' => $row->getId(), 'back' => $back]);
    }

    //########################################

    protected function _toHtml()
    {
        $tempGridIds = [];
        $this->getHelper('Component\Ebay')->isEnabled() && $tempGridIds[] = $this->getId();
        $tempGridIds = $this->getHelper('Data')->jsonEncode($tempGridIds);

        $this->jsPhp->addConstants($this->getHelper('Data')
            ->getClassConstants(\Ess\M2ePro\Model\Log\AbstractModel::class));

        $this->jsUrl->addUrls([
            'ebay_order/view' => $this->getUrl(
                '*/ebay_order/view',
                ['back'=>$this->getHelper('Data')->makeBackUrlParam('*/ebay_order/index')]
            )
        ]);

        $this->jsTranslator->add('View Full Order Log', $this->__('View Full Order Log'));

        $this->js->add(<<<JS
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

    //########################################
}
