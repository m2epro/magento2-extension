<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid;

class Grid extends AbstractGrid
{
    /** @var \Ess\M2ePro\Helper\View */
    protected $viewHelper;

    public function __construct(
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->viewHelper = $viewHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->css->addFile('account/grid.css');

        // Initialize view
        // ---------------------------------------
        $view = $this->viewHelper->getCurrentView();
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
        $this->addColumn('create_date', [
            'header'    => $this->__('Creation Date'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'datetime',
            'filter'    => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
            'format'    => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'     => 'create_date',
            'filter_index' => 'main_table.create_date'
        ]);

        $this->addColumn('update_date', [
            'header'    => $this->__('Update Date'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'datetime',
            'filter'    => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
            'format'    => \IntlDateFormatter::MEDIUM,
            'filter_time' => true,
            'index'     => 'update_date',
            'filter_index' => 'main_table.update_date'
        ]);

        $confirm = 'Attention! By Deleting Account you delete all information on it from M2E Pro Server. ';
        $confirm .= 'This will cause inappropriate work of all Accounts\' copies.';
        $confirm = $this->__($confirm);

        $this->addColumn('actions', [
            'header'    => $this->__('Actions'),
            'align'     => 'left',
            'width'     => '150px',
            'type'      => 'action',
            'index'     => 'actions',
            'filter'    => false,
            'sortable'  => false,
            'getter'    => 'getId',
            'renderer'  => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'actions'   => [
                [
                    'caption'   => $this->__('Delete'),
                    'class'     => 'action-default scalable add primary account-delete-btn',
                    'url'       => ['base'=> '*/*/delete'],
                    'field'     => 'id',
                    'confirm'  => $confirm
                ]
            ]
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/accountGrid', ['_current'=>true]);
    }

    public function getRowUrl($row)
    {
        return $this->viewHelper
            ->getUrl($row, 'account', 'edit', ['id' => $row->getData('id')]);
    }

    //########################################
}
