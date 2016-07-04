<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $_groupedActions = array();
    protected $_actions        = array();

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/grid.css');

        return parent::_prepareLayout();
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn(
            'id', [
                'header' => $this->__('ID'),
                'align' => 'left',
                'type'  => 'number',
                'index' => 'id',
                'filter_index' => 'main_table.id'
            ]
        );

        $this->addColumn('title', array(
            'header'    => $this->__('Title / Info'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'title',
            'filter_index' => 'main_table.title',
            'frame_callback' => array($this, 'callbackColumnTitle'),
            'filter_condition_callback' => array($this, 'callbackFilterTitle')
        ));

        $this->addColumn('products_total_count', array(
            'header'    => $this->__('Total Items'),
            'align'     => 'right',
            'type'      => 'number',
            'index'     => 'products_total_count',
            'filter_index' => 'main_table.products_total_count',
            'frame_callback' => array($this, 'callbackColumnTotalProducts')
        ));

        $this->addColumn('products_active_count', array(
            'header'    => $this->__('Active Items'),
            'align'     => 'right',
            'type'      => 'number',
            'index'     => 'products_active_count',
            'filter_index' => 'main_table.products_active_count',
            'frame_callback' => array($this, 'callbackColumnListedProducts')
        ));

        $this->addColumn('products_inactive_count', array(
            'header'    => $this->__('Inactive Items'),
            'align'     => 'right',
            'width'     => 100,
            'type'      => 'number',
            'index'     => 'products_inactive_count',
            'filter_index' => 'main_table.products_inactive_count',
            'frame_callback' => array($this, 'callbackColumnInactiveProducts')
        ));

        $this->setColumns();

        $this->addColumn('actions', array(
            'header'    => $this->__('Actions'),
            'align'     => 'left',
            'type'      => 'action',
            'index'     => 'actions',
            'filter'    => false,
            'sortable'  => false,
            'getter'    => 'getId',
            'renderer'  => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action',
            'group_order' => $this->getGroupOrder(),
            'actions'     => $this->getColumnActionsItems()
        ));

        return parent::_prepareColumns();
    }

    protected function setColumns() {}

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        return $value;
    }

    protected function callbackFilterTitle($collection, $column) {}

    // ---------------------------------------

    public function callbackColumnTotalProducts($value, $row, $column, $isExport)
    {
        return $this->getColumnValue($value);
    }

    public function callbackColumnListedProducts($value, $row, $column, $isExport)
    {
        return $this->getColumnValue($value);
    }

    public function callbackColumnInactiveProducts($value, $row, $column, $isExport)
    {
        return $this->getColumnValue($value);
    }

    //########################################

    protected function getColumnValue($value)
    {
        if (is_null($value) || $value === '') {
            $value = $this->__('N/A');
        } else if ($value <= 0) {
            $value = '<span style="color: red;">0</span>';
        }

        return $value;
    }

    // ---------------------------------------

    protected function getGroupOrder()
    {
        return array(
            'products_actions' => $this->__('Products'),
            'edit_actions'     => $this->__('Edit Settings'),
            'other'            => $this->__('Other'),
        );
    }

    protected function getColumnActionsItems()
    {
        return array();
    }

    //########################################
}