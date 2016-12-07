<?php

namespace Ess\M2ePro\Block\Adminhtml\Order\View\Log;

use Ess\M2ePro\Block\Adminhtml\Log\AbstractGrid;

class Grid extends AbstractGrid
{
    /** @var $order \Ess\M2ePro\Model\Order */
    private $order;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('orderViewLogGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('create_date');
        $this->setDefaultDir('DESC');
        $this->setFilterVisibility(false);
        $this->setUseAjax(true);
        $this->setCustomPageSize(false);
        // ---------------------------------------

        $this->order = $this->getHelper('Data\GlobalData')->getValue('order');
    }

    protected function _prepareCollection()
    {
        $collection = $this->activeRecordFactory->getObject('Order\Log')->getCollection();
        $collection->addFieldToFilter('order_id', $this->order->getId());

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('create_date', array(
            'header'    => $this->__('Create Date'),
            'align'     => 'left',
            'width'     => '165px',
            'type'      => 'datetime',
            'format'    => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'     => 'create_date'
        ));

        $this->addColumn('message', array(
            'header'    => $this->__('Message'),
            'align'     => 'left',
            'width'     => '*',
            'type'      => 'text',
            'sortable'  => false,
            'filter_index' => 'id',
            'index'     => 'description',
            'frame_callback' => array($this, 'callbackDescription')
        ));

        $this->addColumn('initiator', array(
            'header'    => $this->__('Run Mode'),
            'align'     => 'right',
            'width'     => '65px',
            'index'     => 'initiator',
            'sortable'  => false,
            'type'      => 'options',
            'options'   => $this->_getLogInitiatorList(),
            'frame_callback' => array($this, 'callbackColumnInitiator')
        ));

        $this->addColumn('type', array(
            'header'    => $this->__('Type'),
            'align'     => 'right',
            'width'     => '65px',
            'index'     => 'type',
            'sortable'  => false,
            'type'      => 'options',
            'options'   => $this->_getLogTypeList(),
            'frame_callback' => array($this, 'callbackColumnType')
        ));

        return parent::_prepareColumns();
    }

    //########################################

    public function getRowUrl($row)
    {
        return '';
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/order/viewLogGrid', array('_current' => true));
    }

    //########################################
}