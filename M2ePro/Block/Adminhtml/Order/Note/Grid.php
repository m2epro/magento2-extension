<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Order\Note;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Order\Note\Grid
 */
class Grid extends AbstractGrid
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('orderNoteGrid');

        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setFilterVisibility(false);
        $this->setUseAjax(true);
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->activeRecordFactory->getObject('Order\Note')->getCollection();
        $collection->addFieldToFilter('order_id', $this->getRequest()->getParam('id'));

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', [
            'header'       => $this->__('Description'),
            'align'        => 'left',
            'width'        => '*',
            'type'         => 'text',
            'sortable'     => false,
            'filter_index' => 'id',
            'index'        => 'note'
        ]);

        $this->addColumn('create_date', [
            'header'       => $this->__('Create Date'),
            'align'     => 'left',
            'width'     => '165px',
            'type'      => 'datetime',
            'format'    => \IntlDateFormatter::MEDIUM,
            'index'     => 'create_date'
        ]);

        $this->addColumn('actions', [
            'header'    => $this->__('Actions'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'action',
            'filter'    => false,
            'sortable'  => false,
            'getter'    => 'getId',
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'actions'   => [
                [
                    'caption'        => $this->__('Edit'),
                    'onclick_action' => "OrderNoteObj.openEditNotePopup",
                    'field'          => 'id'
                ],
                [
                    'caption'        => $this->__('Delete'),
                    'onclick_action' => "OrderNoteObj.deleteNote",
                    'field'          => 'id'
                ]
            ]
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
        return $this->getUrl('*/order/noteGrid', ['_current' => true]);
    }

    //########################################
}
