<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs\SynchronizationLog;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Developers\Tabs\SynchronizationLog\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Log\AbstractGrid
{
    protected $actionsTitles;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $task = $this->getRequest()->getParam('task');
        $channel = $this->getRequest()->getParam('channel');

        $this->setId(
            'synchronizationLogGrid' . ($task !== null ? $task : '') . ucfirst($channel)
        );

        $this->setDefaultSort('create_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);

        $filters = [];
        $task !== null && $filters['task'] = $task;
        $task !== null && $filters['component_mode'] = $channel;
        $this->setDefaultFilter($filters);

        $this->actionsTitles = $this->activeRecordFactory->getObject('Synchronization\Log')->getActionsTitles();
    }

    //########################################

    protected function _getLogTypeList()
    {
        return [
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING       => $this->__('Warning'),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR         => $this->__('Error'),
            \Ess\M2ePro\Model\Synchronization\Log::TYPE_FATAL_ERROR => $this->__('Fatal Error')
        ];
    }

    //########################################

    protected function _prepareCollection()
    {
        $components = $this->getHelper('Component')->getEnabledComponents();

        $collection = $this->activeRecordFactory->getObject('Synchronization\Log')->getCollection();
        $collection->getSelect()->where(
            'component_mode IN(\'' . implode('\',\'', $components) . '\') OR component_mode IS NULL'
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'create_date',
            [
                'header'      => $this->__('Date'),
                'align'       => 'left',
                'type'        => 'datetime',
                'filter'      => '\Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime',
                'filter_time' => true,
                'format'      => \IntlDateFormatter::MEDIUM,
                'index'       => 'create_date'
            ]
        );

        $this->addColumn(
            'task',
            [
                'header'                    => $this->__('Task'),
                'align'                     => 'left',
                'type'                      => 'options',
                'index'                     => 'task',
                'sortable'                  => false,
                'filter_index'              => 'task',
                'filter_condition_callback' => [$this, 'callbackFilterTask'],
                'option_groups'             => $this->getActionTitles(),
                'options'                   => $this->actionsTitles
            ]
        );

        $this->addColumn(
            'description',
            [
                'header'         => $this->__('Message'),
                'align'          => 'left',
                'type'           => 'text',
                'string_limit'   => 350,
                'index'          => 'description',
                'filter_index'   => 'main_table.description',
                'frame_callback' => [$this, 'callbackColumnDescription']
            ]
        );

        $this->addColumn(
            'detailed_description',
            [
                'header'         => $this->__('Detailed'),
                'align'          => 'left',
                'type'           => 'text',
                'string_limit'   => 65000,
                'index'          => 'detailed_description',
                'filter_index'   => 'main_table.detailed_description',
                'frame_callback' => [$this, 'callbackColumnDescription']
            ]
        );

        $this->addColumn(
            'type',
            [
                'header'         => $this->__('Type'),
                'index'          => 'type',
                'align'          => 'right',
                'type'           => 'options',
                'sortable'       => false,
                'options'        => $this->_getLogTypeList(),
                'frame_callback' => [$this, 'callbackColumnType']
            ]
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');
    }

    //########################################

    protected function callbackFilterTask($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where(
            "CONCAT_WS('_', main_table.component_mode, main_table.task) LIKE ?",
            $value .'%'
        );
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/*/SynchronizationLogGrid', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function getActionTitles()
    {
        $amazonTitles = $ebayTitles = $walmartTitles = [];

        $skipForAmazon = [];
        $skipForEbay = [
            \Ess\M2ePro\Model\Synchronization\Log::TASK_REPRICING
        ];
        $skipForWalmart = [
            \Ess\M2ePro\Model\Synchronization\Log::TASK_REPRICING
        ];

        foreach ($this->actionsTitles as $value => $label) {
            if (!in_array($value, $skipForEbay)) {
                $ebayTitles[] = [
                    'label' => $label,
                    'value' => \Ess\M2ePro\Helper\View\Ebay::NICK . '_' . $value
                ];
            }

            if (!in_array($value, $skipForAmazon)) {
                $amazonTitles[] = [
                    'label' => $label,
                    'value' => \Ess\M2ePro\Helper\View\Amazon::NICK . '_' . $value
                ];
            }

            if (!in_array($value, $skipForWalmart)) {
                $walmartTitles[] = [
                    'label' => $label,
                    'value' => \Ess\M2ePro\Helper\View\Walmart::NICK . '_' . $value
                ];
            }
        }

        return [
            ['label' => $this->__('eBay Task'), 'value' => $ebayTitles],
            ['label' => $this->__('Amazon Task'), 'value' => $amazonTitles],
            ['label' => $this->__('Walmart Task'), 'value' => $walmartTitles]
        ];
    }

    //########################################
}
