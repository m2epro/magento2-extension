<?php

namespace Ess\M2ePro\Block\Adminhtml\Order\View\Log;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

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
        $this->addColumn('message', array(
            'header'    => $this->__('Message'),
            'align'     => 'left',
            'width'     => '*',
            'type'      => 'text',
            'sortable'  => false,
            'filter_index' => 'id',
            'index'     => 'description',
            'frame_callback' => array($this, 'callbackColumnDescription')
        ));

        $this->addColumn('type', array(
            'header'    => $this->__('Type'),
            'align'     => 'left',
            'width'     => '65px',
            'index'     => 'type',
            'sortable'  => false,
            'frame_callback' => array($this, 'callbackColumnType')
        ));

        $this->addColumn('initiator', array(
            'header'    => $this->__('Run Mode'),
            'align'     => 'left',
            'width'     => '65px',
            'index'     => 'initiator',
            'sortable'  => false,
            'type'      => 'options',
            'options'   => array(
                \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN   => $this->__('Unknown'),
                \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION => $this->__('Automatic'),
                \Ess\M2ePro\Helper\Data::INITIATOR_USER      => $this->__('Manual'),
            ),
            'frame_callback' => array($this, 'callbackColumnInitiator')
        ));

        $this->addColumn('create_date', array(
            'header'    => $this->__('Create Date'),
            'align'     => 'left',
            'width'     => '165px',
            'type'      => 'datetime',
            'format'    => \IntlDateFormatter::MEDIUM,
            'index'     => 'create_date'
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
        switch ($value) {
            case \Ess\M2ePro\Model\Log\AbstractLog::TYPE_SUCCESS:
                $message = '<span style="color: green;">'.$this->__('Success').'</span>';
                break;
            case \Ess\M2ePro\Model\Log\AbstractLog::TYPE_NOTICE:
                $message = '<span style="color: blue;">'.$this->__('Notice').'</span>';
                break;
            case \Ess\M2ePro\Model\Log\AbstractLog::TYPE_WARNING:
                $message = '<span style="color: orange;">'.$this->__('Warning').'</span>';
                break;
            case \Ess\M2ePro\Model\Log\AbstractLog::TYPE_ERROR:
            default:
                $message = '<span style="color: red;">'.$this->__('Error').'</span>';
                break;
        }

        return $message;
    }

    public function callbackColumnInitiator($value, $row, $column, $isExport)
    {
        $initiator = $row->getData('initiator');

        switch ($initiator) {
            case \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION:
                $message = "<span style=\"text-decoration: underline;\">{$value}</span>";
                break;
            case \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN:
                $message = "<span style=\"font-style: italic; color: gray;\">{$value}</span>";
                break;
            case \Ess\M2ePro\Helper\Data::INITIATOR_USER:
            default:
                $message = "<span>{$value}</span>";
                break;
        }

        return $message;
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