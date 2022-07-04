<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    protected $_groupedActions = [];
    protected $_actions        = [];

    /** @var \Ess\M2ePro\Helper\View */
    protected $viewHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    protected $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->viewHelper = $viewHelper;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $backendHelper, $data);
    }

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

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------
        $currentView = $this->viewHelper->getCurrentView();

        // Set clear log action
        // ---------------------------------------
        $this->getMassactionBlock()->addItem(
            'clear_logs',
            [
                'label' => $this->__('Clear Log(s)'),
                'url' => $this->getUrl(
                    '*/listing/clearLog',
                    [
                        'back' => $this->dataHelper->makeBackUrlParam("*/{$currentView}_listing/index")
                    ]
                ),
                'confirm' => $this->__('Are you sure?')
            ]
        );
        // ---------------------------------------

        // Set remove listings action
        // ---------------------------------------
        $this->getMassactionBlock()->addItem(
            'delete_listings',
            [
                'label' => $this->__('Delete Listing(s)'),
                'url' => $this->getUrl("*/{$currentView}_listing/delete"),
                'confirm' => $this->__('Are you sure?')
            ]
        );
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    protected function _prepareColumns()
    {
        $this->addColumn(
            'id',
            [
                'header' => $this->__('ID'),
                'align' => 'left',
                'type'  => 'number',
                'index' => 'id',
                'filter_index' => 'main_table.id'
            ]
        );

        $this->addColumn('title', [
            'header'    => $this->__('Title / Info'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'title',
            'escape'    => false,
            'filter_index' => 'main_table.title',
            'frame_callback' => [$this, 'callbackColumnTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('products_total_count', [
            'header'    => $this->__('Total Items'),
            'align'     => 'right',
            'type'      => 'number',
            'index'     => 'products_total_count',
            'filter_index' => 't.products_total_count',
            'frame_callback' => [$this, 'callbackColumnProductsCount']
        ]);

        $this->addColumn('products_active_count', [
            'header'    => $this->__('Active Items'),
            'align'     => 'right',
            'type'      => 'number',
            'index'     => 'products_active_count',
            'filter_index' => 't.products_active_count',
            'frame_callback' => [$this, 'callbackColumnProductsCount']
        ]);

        $this->addColumn('products_inactive_count', [
            'header'    => $this->__('Inactive Items'),
            'align'     => 'right',
            'width'     => 100,
            'type'      => 'number',
            'index'     => 'products_inactive_count',
            'filter_index' => 't.products_inactive_count',
            'frame_callback' => [$this, 'callbackColumnProductsCount']
        ]);

        $this->setColumns();

        $this->addColumn('actions', [
            'header'    => $this->__('Actions'),
            'align'     => 'left',
            'type'      => 'action',
            'index'     => 'actions',
            'filter'    => false,
            'sortable'  => false,
            'getter'    => 'getId',
            'renderer'  => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'group_order' => $this->getGroupOrder(),
            'actions'     => $this->getColumnActionsItems()
        ]);

        return parent::_prepareColumns();
    }

    protected function setColumns()
    {
        return null;
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        return $value;
    }

    protected function callbackFilterTitle($collection, $column)
    {
        return null;
    }

    //########################################

    public function callbackColumnProductsCount($value, $row, $column, $isExport)
    {
        if ($value === null || $value === '') {
            $value = $this->__('N/A');
        } elseif ($value <= 0) {
            $value = '<span style="color: red;">0</span>';
        }

        return $value;
    }

    // ---------------------------------------

    protected function getGroupOrder()
    {
        return [
            'products_actions' => $this->__('Products'),
            'edit_actions'     => $this->__('Edit Settings'),
            'other'            => $this->__('Other'),
        ];
    }

    protected function getColumnActionsItems()
    {
        return [];
    }

    //########################################
}
