<?php

namespace Ess\M2ePro\Block\Adminhtml\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

class Grid extends AbstractGrid
{
    protected $viewComponentHelper = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->css->addFile('account/grid.css');

        // Initialize view
        // ---------------------------------------
        $view = $this->getHelper('View')->getCurrentView();
        $this->viewComponentHelper = $this->getHelper('View')->getComponentHelper($view);
        // ---------------------------------------

        // Initialization block
        // ---------------------------------------
        $this->setId($view . 'AccountGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('title');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn('create_date', array(
            'header'    => $this->__('Creation Date'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'datetime',
            'format'    => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'     => 'create_date',
            'filter_index' => 'main_table.create_date'
        ));

        $this->addColumn('update_date', array(
            'header'    => $this->__('Update Date'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'datetime',
            'format'    => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'     => 'update_date',
            'filter_index' => 'main_table.update_date'
        ));

        $confirm = 'Attention! By Deleting Account you delete all information on it from M2E Pro Server. ';
        $confirm .= 'This will cause inappropriate work of all Accounts\' copies.';
        $confirm = $this->__($confirm);

        $this->addColumn('actions', array(
            'header'    => $this->__('Actions'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'action',
            'index'     => 'actions',
            'filter'    => false,
            'sortable'  => false,
            'getter'    => 'getId',
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'actions'   => array(
                array(
                    'caption'   => $this->__('Delete'),
                    'class'     => 'action-default scalable add primary account-delete-btn',
                    'url'       => array('base'=> '*/*/delete'),
                    'field'     => 'id',
                    'confirm'  => $confirm
                )
            )
        ));

        return parent::_prepareColumns();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/accountGrid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return $this->getHelper('View')
            ->getUrl($row, 'account', 'edit', array('id' => $row->getData('id')));
    }
}