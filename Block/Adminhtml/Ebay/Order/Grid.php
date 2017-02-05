<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

class Grid extends AbstractGrid
{
    private $itemsCollection = NULL;

    protected $resourceConnection;
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
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
                array('mea' => $this->activeRecordFactory->getObject('Ebay\Account')->getResource()->getMainTable()),
                '(mea.account_id = `main_table`.account_id)',
                array('account_mode' => 'mode'))
            ->joinLeft(
                array('so' => $this->resourceConnection->getTableName('sales_order')),
                '(so.entity_id = `main_table`.magento_order_id)',
                array('magento_order_num' => 'increment_id'));

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
            $collection->addFieldToFilter('magento_order_id', array('null' => true));
        }
        // ---------------------------------------

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _afterLoadCollection()
    {
        $this->itemsCollection = $this->ebayFactory->getObject('Order\Item')->getCollection()
            ->addFieldToFilter('order_id', array('in' => $this->getCollection()->getColumnValues('id')));

        return parent::_afterLoadCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('purchase_create_date', array(
            'header' => $this->__('Sale Date'),
            'align'  => 'left',
            'type'   => 'datetime',
            'format' => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'  => 'purchase_create_date',
            'width'  => '170px',
            'frame_callback' => array($this, 'callbackPurchaseCreateDate'),
        ));

        $this->addColumn('magento_order_num', array(
            'header' => $this->__('Magento Order #'),
            'align'  => 'left',
            'index'  => 'so.increment_id',
            'width'  => '200px',
            'frame_callback' => array($this, 'callbackColumnMagentoOrder')
        ));

        $this->addColumn('ebay_order_id', array(
            'header' => $this->__('eBay Order #'),
            'align'  => 'left',
            'width'  => '145px',
            'index'  => 'ebay_order_id',
            'frame_callback' => array($this, 'callbackColumnEbayOrder'),
            'filter'   => 'Ess\M2ePro\Block\Adminhtml\Ebay\Grid\Column\Filter\OrderId',
            'filter_condition_callback' => array($this, 'callbackFilterEbayOrderId')
        ));

        $this->addColumn('ebay_order_items', array(
            'header' => $this->__('Items'),
            'align'  => 'left',
            'index'  => 'ebay_order_items',
            'sortable' => false,
            'width'  => '*',
            'frame_callback' => array($this, 'callbackColumnItems'),
            'filter_condition_callback' => array($this, 'callbackFilterItems')
        ));

        $this->addColumn('buyer', array(
            'header' => $this->__('Buyer'),
            'align'  => 'left',
            'index'  => 'buyer_user_id',
            'frame_callback' => array($this, 'callbackColumnBuyer'),
            'filter_condition_callback' => array($this, 'callbackFilterBuyer'),
            'width'  => '120px'
        ));

        $this->addColumn('paid_amount', array(
            'header' => $this->__('Total Paid'),
            'align'  => 'left',
            'width'  => '110px',
            'index'  => 'paid_amount',
            'type'   => 'number',
            'frame_callback' => array($this, 'callbackColumnTotal')
        ));

        $this->addColumn('reservation_state', array(
            'header' => $this->__('Reservation'),
            'align'  => 'left',
            'width'  => '50px',
            'index'  => 'reservation_state',
            'type'   => 'options',
            'options' => array(
                \Ess\M2ePro\Model\Order\Reserve::STATE_UNKNOWN  => $this->__('Not Reserved'),
                \Ess\M2ePro\Model\Order\Reserve::STATE_PLACED   => $this->__('Reserved'),
                \Ess\M2ePro\Model\Order\Reserve::STATE_RELEASED => $this->__('Released'),
                \Ess\M2ePro\Model\Order\Reserve::STATE_CANCELED => $this->__('Canceled'),
            )
        ));

        $this->addColumn('checkout_status', array(
            'header' => $this->__('Checkout'),
            'align'  => 'left',
            'width'  => '50px',
            'index'  => 'checkout_status',
            'type'   => 'options',
            'options' => array(
                \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_INCOMPLETE => $this->__('No'),
                \Ess\M2ePro\Model\Ebay\Order::CHECKOUT_STATUS_COMPLETED  => $this->__('Yes')
            ),
            'frame_callback' => array($this, 'callbackColumnCheckoutStatus')
        ));

        $this->addColumn('payment_status', array(
            'header' => $this->__('Paid'),
            'align'  => 'left',
            'width'  => '50px',
            'index'  => 'payment_status',
            'type'   => 'options',
            'options' => array(
                0 => $this->__('No'),
                1 => $this->__('Yes')
            ),
            'frame_callback' => array($this, 'callbackColumnPayment'),
            'filter_condition_callback' => array($this, 'callbackFilterPaymentCondition')
        ));

        $this->addColumn('shipping_status', array(
            'header' => $this->__('Shipped'),
            'align'  => 'left',
            'width'  => '50px',
            'index'  => 'shipping_status',
            'type'   => 'options',
            'options' => array(
                0 => $this->__('No'),
                1 => $this->__('Yes')
            ),
            'frame_callback' => array($this, 'callbackColumnShipping'),
            'filter_condition_callback' => array($this, 'callbackFilterShippingCondition')
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------

        $groups = array(
            'general' => $this->__('General'),
        );

        if ($this->getHelper('Component\Ebay\PickupStore')->isFeatureEnabled()) {
            $groups['in_store_pickup'] = $this->__('In-Store Pickup');
        }

        $this->getMassactionBlock()->setGroups($groups);

        // Set mass-action
        // ---------------------------------------
        $this->getMassactionBlock()->addItem('reservation_place', array(
            'label'    => $this->__('Reserve QTY'),
            'url'      => $this->getUrl('*/order/reservationPlace'),
            'confirm'  => $this->__('Are you sure?')
        ), 'general');

        $this->getMassactionBlock()->addItem('reservation_cancel', array(
            'label'    => $this->__('Cancel QTY Reserve'),
            'url'      => $this->getUrl('*/order/reservationCancel'),
            'confirm'  => $this->__('Are you sure?')
        ), 'general');

        $this->getMassactionBlock()->addItem('ship', array(
            'label'    => $this->__('Mark Order(s) as Shipped'),
            'url'      => $this->getUrl('*/ebay_order/updateShippingStatus'),
            'confirm'  => $this->__('Are you sure?')
        ), 'general');

        $this->getMassactionBlock()->addItem('pay', array(
            'label'    => $this->__('Mark Order(s) as Paid'),
            'url'      => $this->getUrl('*/ebay_order/updatePaymentStatus'),
            'confirm'  => $this->__('Are you sure?')
        ), 'general');

        $this->getMassactionBlock()->addItem('resend_shipping', array(
            'label'    => $this->__('Resend Shipping Information'),
            'url'      => $this->getUrl('*/order/resubmitShippingInfo'),
            'confirm'  => $this->__('Are you sure?')
        ), 'general');
        // ---------------------------------------

        if (!$this->getHelper('Component\Ebay\PickupStore')->isFeatureEnabled()) {
            return parent::_prepareMassaction();
        }

        $this->getMassactionBlock()->addItem('mark_as_ready_for_pickup', array(
            'label'    => $this->__('Mark as Ready For Pickup'),
            'url'      => $this->getUrl('*/ebay_order/markAsReadyForPickup'),
            'confirm'  => $this->__('Are you sure?')
        ), 'in_store_pickup');

        $this->getMassactionBlock()->addItem('mark_as_picked_up', array(
            'label'    => $this->__('Mark as Picked Up'),
            'url'      => $this->getUrl('*/ebay_order/markAsPickedUp'),
            'confirm'  => $this->__('Are you sure?')
        ), 'in_store_pickup');

        $this->getMassactionBlock()->addItem('mark_as_cancelled', array(
            'label'    => $this->__('Mark as Cancelled'),
            'url'      => $this->getUrl('*/ebay_order/markAsCancelled'),
            'confirm'  => $this->__('Are you sure?')
        ), 'in_store_pickup');

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
                $orderUrl = $this->getUrl('sales/order/view', array('order_id' => $magentoOrderId));
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

        if (!$orderLogsCollection->count()) {
            return '';
        }
        // ---------------------------------------

        $summary = $this->createBlock('Order\Log\Grid\LastActions')->setData(array(
            'entity_id' => $orderId,
            'logs'      => $orderLogsCollection->getItems(),
            'view_help_handler' => 'OrderObj.viewOrderHelp',
            'hide_help_handler' => 'OrderObj.hideOrderHelp',
        ));

        return $summary->toHtml();
    }

    // ---------------------------------------

    public function callbackPurchaseCreateDate($value, $row, $column, $isExport)
    {
        return $row->getChildObject()->getData('purchase_create_date');
    }

    public function callbackColumnEbayOrder($value, $row, $column, $isExport)
    {
        $returnString = str_replace('-', '-<br/>', $row->getChildObject()->getData('ebay_order_id'));

        if ($row->getChildObject()->getData('selling_manager_id') > 0) {
            $returnString .= '<br/> [ <b>SM: </b> # ' . $row->getChildObject()->getData('selling_manager_id') . ' ]';
        }

        if (!$this->getHelper('Component\Ebay\PickupStore')->isFeatureEnabled()) {
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
            if (!is_null($product)) {
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

        $returnString .= $this->getHelper('Data')->escapeHtml($row->getData('buyer_user_id'));

        return $returnString;
    }

    public function callbackColumnTotal($value, $row, $column, $isExport)
    {
        return $this->modelFactory->getObject('Currency')->formatPrice(
            $row->getChildObject()->getData('currency'), $row->getChildObject()->getData('paid_amount')
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
        $collection->addFieldToFilter('main_table.id', array('in' => $ordersIds));
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
            'payment_status', array($filterType => \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED)
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
            'shipping_status', array($filterType => \Ess\M2ePro\Model\Ebay\Order::SHIPPING_STATUS_COMPLETED)
        );
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_order/grid', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        $back = $this->getHelper('Data')->makeBackUrlParam(
            '*/ebay_order/index'
        );

        return $this->getUrl('*/ebay_order/view', array('id' => $row->getId(), 'back' => $back));
    }

    //########################################

    protected function _toHtml()
    {
        $tempGridIds = array();
        $this->getHelper('Component\Ebay')->isEnabled() && $tempGridIds[] = $this->getId();
        $tempGridIds = $this->getHelper('Data')->jsonEncode($tempGridIds);

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Log\AbstractModel'));

        $this->jsUrl->addUrls([
            'ebay_order/view' => $this->getUrl(
                '*/ebay_order/view',
                array('back'=>$this->getHelper('Data')->makeBackUrlParam('*/ebay_order/index'))
            ),
            'amazon_order/view' => $this->getUrl(
                '*/amazon_order/view',
                array('back'=>$this->getHelper('Data')->makeBackUrlParam('*/amazon_order/index'))
            ),
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