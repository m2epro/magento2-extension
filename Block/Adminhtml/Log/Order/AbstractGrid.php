<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Order;

abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Log\AbstractGrid
{
    //#######################################

    abstract protected function getComponentMode();

    //#######################################

    public function _construct()
    {
        parent::_construct();

        $this->css->addFile('order/log/grid.css');

        // Initialization block
        // ---------------------------------------
        $this->setId(ucfirst($this->getComponentMode()) . 'OrderLogGrid');
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

        $accountId = (int)$this->getRequest()->getParam($this->getComponentMode() . 'Account', false);
        $marketplaceId = (int)$this->getRequest()->getParam($this->getComponentMode() . 'Marketplace', false);

        if ($accountId) {
            $collection->getSelect()->where('main_table.account_id = ?', $accountId);
        } else {
            $collection->getSelect()->joinLeft(
                [
                    'account_table' => $this->activeRecordFactory->getObject('Account')
                        ->getResource()->getMainTable()
                ],
                'main_table.account_id = account_table.id',
                ['real_account_id' => 'account_table.id']
            );
            $collection->getSelect()->where('account_table.id IS NOT NULL');
        }

        if ($marketplaceId) {
            $collection->getSelect()->where('main_table.marketplace_id = ?', $marketplaceId);
        } else {
            $collection->getSelect()->joinLeft(
                [
                    'marketplace_table' => $this->activeRecordFactory->getObject('Marketplace')
                        ->getResource()->getMainTable()
                ],
                'main_table.marketplace_id = marketplace_table.id',
                ['marketplace_status' => 'marketplace_table.status']
            );
            $collection->getSelect()
                ->where('marketplace_table.status = ?', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
        }

        $collection->getSelect()->joinLeft(
            array('so' => $this->resourceConnection->getTableName('sales_order')),
            '(so.entity_id = `mo`.magento_order_id)',
            array('magento_order_number' => 'increment_id')
        );

        $orderId = $this->getRequest()->getParam('id', false);

        if ($orderId) {
            $collection->addFieldToFilter('main_table.order_id', (int)$orderId);
        }

        $collection->getSelect()->where('main_table.component_mode = ?', $this->getComponentMode());

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
            'filter_time' => true,
//            'format'    => Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM),
            'index'     => 'create_date',
            'filter_index' => 'main_table.create_date'
        ));

        $orderId = $this->getRequest()->getParam('id', false);

        if (!$orderId) {
            $componentNick = $this->getHelper('Component')->getComponentTitle(
                $this->getComponentMode()
            );

            $this->addColumn('channel_order_id', array(
                'header'    => $this->__('%1% Order #', $componentNick),
                'align'     => 'left',
                'sortable'  => false,
                'index'     => 'channel_order_id',
                'frame_callback' => array($this, 'callbackColumnChannelOrderId'),
                'filter_condition_callback' => array($this, 'callbackFilterChannelOrderId')
            ));
        }

        $this->addColumn('magento_order_number', array(
            'header'    => $this->__('Magento Order #'),
            'align'     => 'left',
            'index'     => 'so.increment_id',
            'sortable'      => false,
            'frame_callback' => array($this, 'callbackColumnMagentoOrderNumber')
        ));

        $this->addColumn('description', array(
            'header'    => $this->__('Message'),
            'align'     => 'left',
            'index'     => 'description',
            'frame_callback' => array($this, 'callbackDescription')
        ));

        $this->addColumn('initiator', array(
            'header'    => $this->__('Run Mode'),
            'align'     => 'right',
            'index'     => 'initiator',
            'sortable'  => false,
            'type'      => 'options',
            'options'   => $this->_getLogInitiatorList(),
            'frame_callback' => array($this, 'callbackColumnInitiator')
        ));

        $this->addColumn('type', array(
            'header'    => $this->__('Type'),
            'align'     => 'right',
            'index'     => 'type',
            'type'      => 'options',
            'sortable'  => false,
            'options'   => $this->_getLogTypeList(),
            'frame_callback' => array($this, 'callbackColumnType')
        ));

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnChannelOrderId($value, $row, $column, $isExport)
    {
        $order = $this->parentFactory
            ->getObjectLoaded($row['component_mode'], 'Order', $row->getData('order_id'), NULL, false);

        if (is_null($order) || is_null($order->getChildObject()->getId())) {
            return $this->__('N/A');
        }

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
            $url = $this->getUrl('sales/order/view', array('order_id' => $magentoOrderId));
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

        $ordersIds = array_unique($ordersIds);

        $collection->addFieldToFilter('main_table.order_id', array('in' => $ordersIds));
    }

    //########################################

    public function getRowUrl($row)
    {
        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', [
            '_current' => true
        ]);
    }

    //########################################
}