<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order\View\Log;

use Ess\M2ePro\Block\Adminhtml\Log\AbstractGrid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Order\View\Log\Grid
 */
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
        $this->addColumn('create_date', [
            'header'    => $this->__('Create Date'),
            'align'     => 'left',
            'width'     => '165px',
            'type'      => 'datetime',
            'filter'    => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime',
            'format'    => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'     => 'create_date'
        ]);

        $this->addColumn('message', [
            'header'    => $this->__('Message'),
            'align'     => 'left',
            'width'     => '*',
            'type'      => 'text',
            'sortable'  => false,
            'filter_index' => 'id',
            'index'     => 'description',
            'frame_callback' => [$this, 'callbackDescription']
        ]);

        $this->addColumn('initiator', [
            'header'    => $this->__('Run Mode'),
            'align'     => 'right',
            'width'     => '65px',
            'index'     => 'initiator',
            'sortable'  => false,
            'type'      => 'options',
            'options'   => $this->_getLogInitiatorList(),
            'frame_callback' => [$this, 'callbackColumnInitiator']
        ]);

        $this->addColumn('type', [
            'header'    => $this->__('Type'),
            'align'     => 'right',
            'width'     => '65px',
            'index'     => 'type',
            'sortable'  => false,
            'type'      => 'options',
            'options'   => $this->_getLogTypeList(),
            'frame_callback' => [$this, 'callbackColumnType']
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function getRowUrl($row)
    {
        return '';
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/order/viewLogGrid', ['_current' => true]);
    }

    //########################################
}
