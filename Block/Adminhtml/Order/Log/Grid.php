<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order\Log;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

class Grid extends AbstractGrid
{
    /** @var  \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    //#######################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    )
    {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $channel = $this->getRequest()->getParam('channel');

        // Initialization block
        // ---------------------------------------
        $this->setId(ucfirst($channel) . 'OrderLogGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('create_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setCustomPageSize(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $collection = $this->activeRecordFactory->getObject('Order\Log')->getCollection();

        $collection->getSelect()->joinLeft(
            array('mo' => $this->activeRecordFactory->getObject('Order')->getResource()->getMainTable()),
            '(mo.id = `main_table`.order_id)',
            array('magento_order_id')
        );

        $componentNick = $this->getHelper('Data\GlobalData')->getValue('component_nick');

        if ($componentNick) {
            $accountId = (int)$this->getRequest()->getParam($componentNick.'Account', false);
            $marketplaceId = (int)$this->getRequest()->getParam($componentNick.'Marketplace', false);

            if ($accountId) {
                $collection->getSelect()->where('mo.account_id = ?', $accountId);
            }

            if ($marketplaceId) {
                $collection->getSelect()->where('mo.marketplace_id = ?', $marketplaceId);
            }
        }

        $collection->getSelect()->joinLeft(
            array('so' => $this->resourceConnection->getTableName('sales_order')),
            '(so.entity_id = `mo`.magento_order_id)',
            array('magento_order_number' => 'increment_id')
        );

        $orderId = $this->getRequest()->getParam('order_id');
        if ($orderId && !$this->getRequest()->isXmlHttpRequest()) {
            $collection->addFieldToFilter('main_table.order_id', (int)$orderId);

            /** @var \Ess\M2ePro\Model\Order $order */
            $order = $this->activeRecordFactory->getObjectLoaded('Order', (int)$orderId);
            $channelOrderId = $order->getData($order->getComponentMode().'_order_id');

            $this->_setFilterValues(array(
                'channel_order_id' => $channelOrderId,
                'component_mode'   => $order->getComponentMode(),
            ));
        }

        $channel = $this->getRequest()->getParam('channel');
        if (!empty($channel)) {
            $collection->getSelect()->where('main_table.component_mode = ?', $channel);
        } else {
            $components = $this->getHelper('View')->getComponentHelper()->getEnabledComponents();
            $collection->addFieldToFilter('main_table.component_mode', array('in'=>$components));
        }

        // ---------------------------------------
        if ($this->getRequest()->getParam('sort', 'create_date') == 'create_date') {
            $collection->setOrder('id', $this->getRequest()->getParam('dir', 'DESC'));
        }
        // ---------------------------------------

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('create_date', array(
            'header'    => $this->__('Creation Date'),
            'align'     => 'left',
            'type'      => 'datetime',
//            'format'    => Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM),
            'index'     => 'create_date',
            'filter_index' => 'main_table.create_date'
        ));

        $this->addColumn('magento_order_number', array(
            'header'    => $this->__('Magento Order #'),
            'align'     => 'left',
            'index'     => 'so.increment_id',
            'sortable'      => false,
            'frame_callback' => array($this, 'callbackColumnMagentoOrderNumber')
        ));

        $this->addColumn('channel_order_id', array(
            'header'    => $this->__('Order #'),
            'align'     => 'left',
            'sortable'  => false,
            'index'     => 'channel_order_id',
            'frame_callback' => array($this, 'callbackColumnChannelOrderId'),
            'filter_condition_callback' => array($this, 'callbackFilterChannelOrderId')
        ));

        $this->addColumn('description', array(
            'header'    => $this->__('Description'),
            'align'     => 'left',
            'index'     => 'description',
            'frame_callback' => array($this, 'callbackColumnDescription')
        ));

        $this->addColumn('initiator', array(
            'header'    => $this->__('Run Mode'),
            'align'     => 'left',
            'index'     => 'initiator',
            'sortable'      => false,
            'type'      => 'options',
            'options'   => array(
                \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN   => $this->__('Unknown'),
                \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION => $this->__('Automatic'),
                \Ess\M2ePro\Helper\Data::INITIATOR_USER      => $this->__('Manual'),
            ),
            'frame_callback' => array($this, 'callbackColumnInitiator')
        ));

        $this->addColumn('type', array(
            'header'    => $this->__('Type'),
            'align'     => 'left',
            'index'     => 'type',
            'type'      => 'options',
            'sortable'      => false,
            'options'   => array(
                \Ess\M2ePro\Model\Log\AbstractLog::TYPE_NOTICE => $this->__('Notice'),
                \Ess\M2ePro\Model\Log\AbstractLog::TYPE_SUCCESS => $this->__('Success'),
                \Ess\M2ePro\Model\Log\AbstractLog::TYPE_WARNING => $this->__('Warning'),
                \Ess\M2ePro\Model\Log\AbstractLog::TYPE_ERROR => $this->__('Error'),
            ),
            'frame_callback' => array($this, 'callbackColumnType')
        ));

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnDescription($value, $row, $column, $isExport)
    {
        return $this->getHelper('View')->getModifiedLogMessage($row->getData('description'));
    }

    public function callbackColumnType($value, $row, $column, $isExport)
    {
        $type = $row->getData('type');

        switch ($type) {
            case \Ess\M2ePro\Model\Log\AbstractLog::TYPE_SUCCESS:
                $message = "<span style=\"color: green;\">{$value}</span>";
                break;
            case \Ess\M2ePro\Model\Log\AbstractLog::TYPE_NOTICE:
                $message = "<span style=\"color: blue;\">{$value}</span>";
                break;
            case \Ess\M2ePro\Model\Log\AbstractLog::TYPE_WARNING:
                $message = "<span style=\"color: orange;\">{$value}</span>";
                break;
            case \Ess\M2ePro\Model\Log\AbstractLog::TYPE_ERROR:
            default:
                $message = "<span style=\"color: red;\">{$value}</span>";
                break;
        }

        return $message;
    }

    public function callbackColumnInitiator($value, $row, $column, $isExport)
    {
        $initiator = $row->getData('initiator');

        switch ($initiator) {
            case \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN:
                $style = 'font-style: italic; color: gray;';
                break;
            case \Ess\M2ePro\Helper\Data::INITIATOR_USER:
            default:
                $style = '';
                break;
        }

        return "<span style='{$style} padding: 0 10px;'>{$value}</span>";
    }

    public function callbackColumnChannelOrderId($value, $row, $column, $isExport)
    {
        $order = $this->parentFactory
            ->getObjectLoaded($row['component_mode'], 'Order', $row->getData('order_id'), NULL, false);

        if (is_null($order) || is_null($order->getChildObject()->getId())) {
            return $this->__('N/A');
        }

        // todo order Urls
        switch ($order->getComponentMode()) {
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                $channelOrderId = $order
                    ->getChildObject()->getData('ebay_order_id');
                $url = $this->getUrl('*/ebay_order/view', array('id' => $row->getData('order_id')));
                break;
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                $channelOrderId = $order
                    ->getChildObject()->getData('amazon_order_id');
                $url = $this->getUrl('*/amazon_order/view', array('id' => $row->getData('order_id')));
                break;
//            todo NOT SUPPORTED FEATURES
//            case \Ess\M2ePro\Helper\Component\Buy::NICK:
//                $channelOrderId = $order->getData('buy_order_id');
//                $url = $this->getUrl('*/adminhtml_common_buy_order/view', array('id' => $row->getData('order_id')));
//                break;
            default:
                $channelOrderId = $this->__('N/A');
                $url = '#';
        }

        return '<a href="'.$url.'" target="_blank">'.$this->getHelper('Data')->escapeHtml($channelOrderId).'</a>';
    }

    public function callbackColumnMagentoOrderNumber($value, $row, $column, $isExport)
    {
        $magentoOrderId = $row->getData('magento_order_id');
        $magentoOrderNumber = $row->getData('magento_order_number');

        if (!$magentoOrderId) {
            $result = $this->__('N/A');
        } else {
            $url = $this->getUrl('adminhtml/sales/order/view', array('order_id' => $magentoOrderId));
            $result = '<a href="'.$url.'" target="_blank">'
                        .$this->getHelper('Data')->escapeHtml($magentoOrderNumber).'</a>';
        }

        return "<span style='min-width: 110px; display: block;'>{$result}</span>";
    }

    public function callbackFilterChannelOrderId($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $ordersIds = array();

        if ($this->getHelper('Component\Ebay')->isEnabled()) {
            $tempOrdersIds = $this->activeRecordFactory->getObject('Ebay\Order')
                ->getCollection()
                ->addFieldToFilter('ebay_order_id', array('like' => '%'.$value.'%'))
                ->getColumnValues('order_id');
            $ordersIds = array_merge($ordersIds, $tempOrdersIds);
        }

        if ($this->getHelper('Component\Amazon')->isEnabled()) {
            $tempOrdersIds = $this->activeRecordFactory->getObject('Amazon\Order')
                ->getCollection()
                ->addFieldToFilter('amazon_order_id', array('like' => '%'.$value.'%'))
                ->getColumnValues('order_id');
            $ordersIds = array_merge($ordersIds, $tempOrdersIds);
        }

//        todo NOT SUPPORTED FEATURES
//        if ($this->getHelper('Component\Buy')->isActive()) {
//            $tempOrdersIds = $this->activeRecordFactory->getObject('Buy\Order')
//                ->getCollection()
//                ->addFieldToFilter('buy_order_id', array('like' => '%'.$value.'%'))
//                ->getColumnValues('order_id');
//            $ordersIds = array_merge($ordersIds, $tempOrdersIds);
//        }

        $ordersIds = array_unique($ordersIds);

        $collection->addFieldToFilter('main_table.order_id', array('in' => $ordersIds));
    }

    //########################################

    public function getRowUrl($row)
    {
        return '';
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array(
            'channel' => $this->getRequest()->getParam('channel')
        ));
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('log/grid.css');
        parent::_prepareLayout();
    }

    //########################################
}