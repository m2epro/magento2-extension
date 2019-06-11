<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Order;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;
use Ess\M2ePro\Model\Walmart\Listing\Product;

class Grid extends AbstractGrid
{
    private $itemsCollection = NULL;

    protected $resourceConnection;
    protected $walmartFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->walmartFactory = $walmartFactory;
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

    protected function _prepareCollection()
    {
        $collection = $this->walmartFactory->getObject('Order')->getCollection();

        $collection->getSelect()
            ->joinLeft(
                array('so' => $this->getHelper('Module\Database\Structure')->getTableNameWithPrefix('sales_order')),
                '(so.entity_id = `main_table`.magento_order_id)',
                array('magento_order_num' => 'increment_id'));

        // Add Filter By Account
        // ---------------------------------------
        if ($accountId = $this->getRequest()->getParam('walmartAccount')) {
            $collection->addFieldToFilter('main_table.account_id', $accountId);
        }
        // ---------------------------------------

        // Add Filter By Marketplace
        // ---------------------------------------
        if ($marketplaceId = $this->getRequest()->getParam('walmartMarketplace')) {
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
        $this->itemsCollection = $this->walmartFactory->getObject('Order\Item')
            ->getCollection()
            ->addFieldToFilter('order_id', array('in' => $this->getCollection()->getColumnValues('id')));

        return parent::_afterLoadCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('purchase_create_date', array(
            'header' => $this->__('Sale Date'),
            'align'  => 'left',
            'type'   => 'datetime',
            'filter' => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime',
            'format' => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'  => 'purchase_create_date',
            'width'  => '170px',
            'frame_callback' => array($this, 'callbackPurchaseCreateDate')
        ));

        $this->addColumn('magento_order_num', array(
            'header' => $this->__('Magento Order #'),
            'align'  => 'left',
            'index'  => 'so.increment_id',
            'width'  => '110px',
            'frame_callback' => array($this, 'callbackColumnMagentoOrder')
        ));

        $this->addColumn('walmart_order_id', array(
            'header' => $this->__('Walmart Order #'),
            'align'  => 'left',
            'width'  => '110px',
            'index'  => 'walmart_order_id',
            'frame_callback' => array($this, 'callbackColumnWalmartOrderId')
        ));

        $this->addColumn('walmart_order_items', array(
            'header' => $this->__('Items'),
            'align'  => 'left',
            'index'  => 'walmart_order_items',
            'sortable' => false,
            'width'  => '*',
            'frame_callback' => array($this, 'callbackColumnItems'),
            'filter_condition_callback' => array($this, 'callbackFilterItems')
        ));

        $this->addColumn('buyer', array(
            'header' => $this->__('Buyer'),
            'align'  => 'left',
            'index'  => 'buyer_name',
            'width'  => '120px',
            'frame_callback' => array($this, 'callbackColumnBuyer'),
            'filter_condition_callback' => array($this, 'callbackFilterBuyer')
        ));

        $this->addColumn('paid_amount', array(
            'header' => $this->__('Total Paid'),
            'align'  => 'left',
            'width'  => '110px',
            'index'  => 'paid_amount',
            'type'   => 'number',
            'frame_callback' => array($this, 'callbackColumnTotal')
        ));

        $this->addColumn('status', array(
            'header'  => $this->__('Status'),
            'align'   => 'left',
            'width'   => '50px',
            'index'   => 'status',
            'filter_index' => 'second_table.status',
            'type'    => 'options',
            'options' => array(
                \Ess\M2ePro\Model\Walmart\Order::STATUS_CREATED             => $this->__('Created'),
                \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED           => $this->__('Unshipped'),
                \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED_PARTIALLY   => $this->__('Partially Shipped'),
                \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED             => $this->__('Shipped'),
                \Ess\M2ePro\Model\Walmart\Order::STATUS_CANCELED            => $this->__('Canceled')
            ),
            'frame_callback' => array($this, 'callbackColumnStatus')
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

        // Set mass-action
        // ---------------------------------------

        $this->getMassactionBlock()->addItem('ship', array(
            'label'    => $this->__('Mark Order(s) as Shipped'),
            'url'      => $this->getUrl('*/walmart_order/updateShippingStatus'),
            'confirm'  => $this->__('Are you sure?')
        ));

        $this->getMassactionBlock()->addItem('resend_shipping', array(
            'label'    => $this->__('Resend Shipping Information'),
            'url'      => $this->getUrl('*/order/resubmitShippingInfo'),
            'confirm'  => $this->__('Are you sure?')
        ));
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    public function callbackPurchaseCreateDate($value, $row, $column, $isExport)
    {
        return $this->_localeDate->formatDate(
            $row->getChildObject()->getData('purchase_create_date'), \IntlDateFormatter::MEDIUM, true
        );
    }

    public function callbackColumnWalmartOrderId($value, $row, $column, $isExport)
    {
        $orderId = $this->getHelper('Data')->escapeHtml($row->getChildObject()->getData('walmart_order_id'));
        $url = $this->getHelper('Component\Walmart')->getOrderUrl($orderId, $row->getData('marketplace_id'));

        return <<<HTML
<a href="{$url}" target="_blank">{$orderId}</a>
HTML;
    }

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

        if (!$orderLogsCollection->getSize()) {
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

                $skuHtml = <<<STRING
<span style="padding-left: 10px;"><b>{$skuLabel}:</b>&nbsp;{$sku}</span><br/>
STRING;
            }

            $itemTitle = $this->getHelper('Data')->escapeHtml($item->getChildObject()->getTitle());
            $qtyLabel = $this->__('QTY');
            $qtyHtml = <<<HTML
<span style="padding-left: 10px;"><b>{$qtyLabel}:</b> {$item->getChildObject()->getQty()}</span>
HTML;

            $html .= <<<HTML
{$itemTitle}&nbsp;{$editItemHtml}<br/>
<small>{$skuHtml}{$qtyHtml}</small>
HTML;
        }

        return $html;
    }

    public function callbackColumnBuyer($value, $row, $column, $isExport)
    {
        if ($row->getChildObject()->getData('buyer_name') == '') {
            return $this->__('N/A');
        }

        $html = $this->getHelper('Data')->escapeHtml($row->getChildObject()->getData('buyer_name'));

        if ($row->getChildObject()->getData('buyer_email') != '') {
            $html .= '<br/>';
            $html .= '&lt;' . $this->getHelper('Data')->escapeHtml($row->getChildObject()->getData('buyer_email'))
                  . '&gt;';
        }

        return $html;
    }

    public function callbackColumnTotal($value, $row, $column, $isExport)
    {
        $currency = $row->getChildObject()->getData('currency');

        if (empty($currency)) {
            /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
            $marketplace = $this->walmartFactory->getCachedObjectLoaded(
                'Marketplace', $row->getData('marketplace_id')
            );
            /** @var \Ess\M2ePro\Model\Walmart\Marketplace $walmartMarketplace */
            $walmartMarketplace = $marketplace->getChildObject();

            $currency = $walmartMarketplace->getDefaultCurrency();
        }

        return $this->modelFactory->getObject('Currency')->formatPrice(
            $currency, $row->getChildObject()->getData('paid_amount')
        );
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $statuses = array(
            \Ess\M2ePro\Model\Walmart\Order::STATUS_CREATED             => $this->__('Created'),
            \Ess\M2ePro\Model\Walmart\Order::STATUS_UNSHIPPED           => $this->__('Unshipped'),
            \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED_PARTIALLY   => $this->__('Partially Shipped'),
            \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED             => $this->__('Shipped'),
            \Ess\M2ePro\Model\Walmart\Order::STATUS_CANCELED            => $this->__('Canceled')
        );
        $status = $row->getChildObject()->getData('status');

        $value = $statuses[$status];

        $statusColors = array(
            \Ess\M2ePro\Model\Walmart\Order::STATUS_CREATED  => 'gray',
            \Ess\M2ePro\Model\Walmart\Order::STATUS_SHIPPED  => 'green',
            \Ess\M2ePro\Model\Walmart\Order::STATUS_CANCELED => 'red'
        );

        $color = isset($statusColors[$status]) ? $statusColors[$status] : 'black';
        $value = '<span style="color: '.$color.';">'.$value.'</span>';

        if ($row->isSetProcessingLock('update_order_status')) {
            $value .= '<br/>';
            $value .= '<span style="color: gray;">['
                .$this->__('Status Update in Progress...').']</span>';
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

        $orderItemsCollection = $this->walmartFactory->getObject('Order\Item')->getCollection();

        $orderItemsCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $orderItemsCollection->getSelect()->columns('order_id');
        $orderItemsCollection->getSelect()->distinct(true);

        $orderItemsCollection->getSelect()->where('title LIKE ? OR sku LIKE ?', '%'.$value.'%');

        $totalResult = $orderItemsCollection->getColumnValues('order_id');
        $collection->addFieldToFilter('main_table.id', array('in' => $totalResult));
    }

    protected function callbackFilterBuyer($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $collection
            ->getSelect()
            ->where('buyer_email LIKE ? OR buyer_name LIKE ?', '%'.$value.'%');
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/walmart_order/grid', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        $back = $this->getHelper('Data')->makeBackUrlParam('*/walmart_order/index');

        return $this->getUrl('*/walmart_order/view', array('id' => $row->getId(), 'back' => $back));
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

        $tempGridIds = array();
        $this->getHelper('Component\Walmart')->isEnabled() && $tempGridIds[] = $this->getId();

        $tempGridIds = $this->getHelper('Data')->jsonEncode($tempGridIds);

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Log\AbstractModel'));

        $this->jsUrl->addUrls([
            'walmart_order/view' => $this->getUrl(
                '*/walmart_order/view',
                array('back'=>$this->getHelper('Data')->makeBackUrlParam('*/walmart_order/index'))
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