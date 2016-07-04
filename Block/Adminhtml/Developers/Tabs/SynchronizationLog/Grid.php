<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs\SynchronizationLog;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Log\Grid\AbstractGrid
{
    protected $actionsTitles;

    //########################################

    public function _construct()
    {
        parent::_construct();

        $task = $this->getRequest()->getParam('task');
        $channel = $this->getRequest()->getParam('channel');

        // Initialization block
        // ---------------------------------------
        $this->setId(
            'synchronizationLogGrid' . (!is_null($task) ? $task : '') . ucfirst($channel)
        );
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('create_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);

        $filters = array();
        !is_null($task) && $filters['task'] = $task;
        !is_null($task) && $filters['component_mode'] = $channel;
        $this->setDefaultFilter($filters);
        // ---------------------------------------

        $this->actionsTitles = $this->activeRecordFactory->getObject('Synchronization\Log')->getActionsTitles();
    }

    //########################################

    protected function _prepareCollection()
    {
        // Get collection logs
        // ---------------------------------------
        $collection = $this->activeRecordFactory->getObject('Synchronization\Log')->getCollection();
        // ---------------------------------------

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'create_date',
                'task',
                'description',
                'type'
            ]
        );

        $ebayComponent = $this->getHelper('View\Ebay\Component')->getEnabledComponents();
        $amazonComponent = $this->getHelper('View\Amazon\Component')->getEnabledComponents();
        $components = array_merge($ebayComponent, $amazonComponent);
        $collection->getSelect()->where(
            'component_mode IN(\'' . implode('\',\'', $components) . '\') OR component_mode IS NULL'
        );

        // we need sort by id also, because create_date may be same for some adjacents entries
        // ---------------------------------------
        if ($this->getRequest()->getParam('sort', 'create_date') == 'create_date') {
            $collection->setOrder('id', $this->getRequest()->getParam('dir', 'DESC'));
        }
        // ---------------------------------------

        // Set collection to grid
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('create_date', array(
            'header'    => $this->__('Creation Date'),
            'align'     => 'left',
            'type'      => 'datetime',
//            'format'    => Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM),
            'index'     => 'create_date'
        ));

        $this->addColumn('task', array(
            'header'    => $this->__('Task'),
            'align'     => 'left',
            'type'      => 'options',
            'index'     => 'task',
            'sortable'  => false,
            'filter_index' => 'task',
            'filter_condition_callback' => array($this, 'callbackFilterTask'),
            'option_groups' => $this->getActionTitles(),
            'options' => $this->actionsTitles
        ));

        $this->addColumn('description', array(
            'header'    => $this->__('Description'),
            'align'     => 'left',
            'type'      => 'text',
            'index'     => 'description',
            'filter_index' => 'main_table.description',
            'frame_callback' => array($this, 'callbackDescription')
        ));

        $this->addColumn('type', array(
            'header'=> $this->__('Type'),
            'index' => 'type',
            'align'     => 'right',
            'type'  => 'options',
            'sortable'  => false,
            'options' => $this->_getLogTypeList(),
            'frame_callback' => array($this, 'callbackColumnType')
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------
    }

    //########################################

    public function callbackDescription($value, $row, $column, $isExport)
    {
        $fullDescription = $this->getHelper('View')->getModifiedLogMessage($row->getData('description'));

        $row->setData('description', $fullDescription);
        $renderedText = $column->getRenderer()->render($row);

        $fullDescription = $this->escapeHtml($fullDescription);

        if (strlen($fullDescription) == strlen($renderedText)) {
            return $renderedText;
        }

        $row->setData('description', strip_tags($fullDescription));
        $renderedText = $column->getRenderer()->render($row);

        $title = $this->actionsTitles[$row->getData('task')] . ' on ' . $row->getData('create_date');

        $renderedText .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                          (<a href="javascript:void(0)" onclick="LogObj.showFullText(this,\''.$title.'\');">more</a>)
                          <div style="display: none;"><br/>'.$fullDescription.'<br/><br/></div>';

        return $renderedText;
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
            $value .'%');
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
        $amazonTitles = $ebayTitles = [];

        foreach ($this->actionsTitles as $value => $label) {
            $amazonTitles[] = [
                'label' => $label,
                'value' => \Ess\M2ePro\Helper\View\Amazon::NICK . '_' . $value
            ];
            $ebayTitles[] = [
                'label' => $label,
                'value' => \Ess\M2ePro\Helper\View\Ebay::NICK . '_' . $value
            ];
        }

        return [
            ['label' => $this->__('eBay Task'), 'value' => $ebayTitles],
            ['label' => $this->__('Amazon Task'), 'value' => $amazonTitles]
        ];
    }

    //########################################
}