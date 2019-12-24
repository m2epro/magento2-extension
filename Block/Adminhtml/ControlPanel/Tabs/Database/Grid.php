<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database;

use Ess\M2ePro\Model\ResourceModel\Collection\Custom;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database\Grid
 */
class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private $customCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Collection\CustomFactory $customCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->customCollectionFactory = $customCollectionFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->_isExport = true;

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelDatabaseGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('component');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setDefaultLimit(50);
        // ---------------------------------------
    }

   //########################################

    protected function _prepareCollection()
    {
        $magentoHelper   = $this->helperFactory->getObject('Magento');
        $structureHelper = $this->helperFactory->getObject('Module_Database_Structure');

        $tablesList = $magentoHelper->getMySqlTables();
        foreach ($tablesList as &$tableName) {
            $tableName = str_replace($magentoHelper->getDatabaseTablesPrefix(), '', $tableName);
        }

        $tablesList = array_unique(array_merge($tablesList, $structureHelper->getMySqlTables()));

        /** @var Custom $collection */
        $collection = $this->customCollectionFactory->create();

        foreach ($tablesList as $tableName) {
            if (!$structureHelper->isModuleTable($tableName)) {
                continue;
            }

            $tableRow = [
                'table_name' => $tableName,
                'component'  => '',
                'group'      => '',
                'is_exist'   => $isExists = $structureHelper->isTableExists($tableName),
                'is_crashed' => $isExists ? !$structureHelper->isTableStatusOk($tableName) : false,
                'records'    => 0,
                'size'       => 0,
                'model'      => $structureHelper->getTableModel($tableName)
            ];

            if ($tableRow['is_exist'] && !$tableRow['is_crashed']) {
                $tableRow['component'] = $structureHelper->getTableComponent($tableName);
                $tableRow['group']     = $structureHelper->getTableGroup($tableName);
                $tableRow['size']      = $structureHelper->getDataLength($tableName);
                $tableRow['records']   = $structureHelper->getCountOfRecords($tableName);
            }

            $collection->addItem(new \Magento\Framework\DataObject($tableRow));
        }

        $this->setCollection($collection);
        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('table_name', [
            'header'    => $this->__('Table Name'),
            'align'     => 'left',
            'index'     => 'table_name',
            'filter_index' => 'table_name',
            'frame_callback' => [$this, 'callbackColumnTableName'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle'],
        ]);

        // ---------------------------------------
        $options['general'] = 'General';
        $options = array_merge($options, $this->helperFactory->getObject('Component')->getComponentsTitles());

        $this->addColumn('component', [
            'header'    => $this->__('Component'),
            'align'     => 'right',
            'width'     => '120px',
            'index'     => 'component',
            'type'      => 'options',
            'options'   => $options,
            'filter_index' => 'component',
            'filter_condition_callback' => [$this, 'callbackFilterMatch'],
        ]);
        // ---------------------------------------

        // ---------------------------------------
        $options = [
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_CONFIGS           => 'Configs',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_ACCOUNTS          => 'Accounts',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_MARKETPLACES      => 'Marketplaces',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_LISTINGS          => 'Listings',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_LISTINGS_PRODUCTS => 'Listings Products',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_LISTINGS_OTHER    => 'Listings Other',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_LOGS              => 'Logs',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_ITEMS             => 'Items',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_PROCESSING        => 'Processing',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_CONNECTORS        => 'Connectors',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_DICTIONARY        => 'Dictionary',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_ORDERS            => 'Orders',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_TEMPLATES         => 'Templates',
            \Ess\M2ePro\Helper\Module\Database\Structure::TABLE_GROUP_OTHER             => 'Other'
        ];

        $this->addColumn('group', [
            'header'    => $this->__('Group'),
            'align'     => 'right',
            'width'     => '100px',
            'index'     => 'group',
            'type'      => 'options',
            'options'   => $options,
            'filter_index' => 'group',
            'filter_condition_callback' => [$this, 'callbackFilterMatch'],
        ]);
        // ---------------------------------------

        $this->addColumn('records', [
            'header'    => $this->__('Records'),
            'align'     => 'right',
            'width'     => '100px',
            'index'     => 'records',
            'type'      => 'number',
            'filter'    => false,
        ]);

        $this->addColumn('size', [
            'header'    => $this->__('Size (Mb)'),
            'align'     => 'right',
            'width'     => '100px',
            'index'     => 'size',
            'filter'    => false,
        ]);

        return parent::_prepareColumns();
    }

    //########################################

    public function callbackColumnTableName($value, $row, $column, $isExport)
    {
        if (!$row->getData('is_exist')) {
            return "<p style=\"color: red; font-weight: bold;\">{$value} [table is not exists]</p>";
        }

        if ($row->getData('is_crashed')) {
            return "<p style=\"color: orange; font-weight: bold;\">{$value} [table is crashed]</p>";
        }

        if (!$row->getData('model')) {
            return "<p style=\"color: #878787;\">{$value}</p>";
        }

        return "<p>{$value}</p>";
    }

    //########################################

    protected function _prepareMassaction()
    {
        // Set massaction identifiers
        // ---------------------------------------
        $this->setMassactionIdField('table_name');
        $this->getMassactionBlock()->setFormFieldName('tables');
        $this->getMassactionBlock()->setUseSelectAll(false);
        // ---------------------------------------

        // Set edit action
        // ---------------------------------------
        $this->getMassactionBlock()->addItem('edit', [
            'label'    => $this->__('Edit Table(s)'),
            'url'      => $this->getUrl('*/controlPanel_database/manageTables')
        ]);
        // ---------------------------------------

        // Set truncate action
        // ---------------------------------------
        $this->getMassactionBlock()->addItem('truncate', [
            'label'    => $this->__('Truncate Table(s)'),
            'url'      => $this->getUrl('*/controlPanel_database/truncateTables'),
            'confirm'  => $this->__('Are you sure?')
        ]);
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    //########################################

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        $gridJsObj = $this->getId().'JsObject';

        $this->js->addRequireJs([
            'jQuery' => 'jquery'
        ], <<<JS

            $$('#controlPanelDatabaseGrid_filter_component',
               '#controlPanelDatabaseGrid_filter_status',
               '#controlPanelDatabaseGrid_filter_group').each(function(el) {
                    el.observe('change', function() {
                        {$gridJsObj}.doFilter();
                    });
                });
JS
        );
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl('*/controlPanel/databaseTab', ['_current'=>true]);
    }

    public function getRowUrl($row)
    {
        if (!$row->getData('is_exist') || $row->getData('is_crashed') || !$row->getData('model')) {
            return false;
        }

        return $this->getUrl(
            '*/controlPanel_database/manageTable',
            ['table' => $row->getData('table_name')]
        );
    }

    //########################################

    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection() && $column->getFilterConditionCallback()) {
            call_user_func($column->getFilterConditionCallback(), $this->getCollection(), $column);
        }
        return $this;
    }

    //########################################

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $this->getCollection()->addFilter(
            'table_name',
            $value,
            \Ess\M2ePro\Model\ResourceModel\Collection\Custom::CONDITION_LIKE
        );
    }

    protected function callbackFilterMatch($collection, $column)
    {
        $field = $column->getFilterIndex() ? $column->getFilterIndex()
            : $column->getIndex();

        $value = $column->getFilter()->getValue();
        if ($value == null || empty($field)) {
            return;
        }

        $this->getCollection()->addFilter(
            $field,
            $value,
            \Ess\M2ePro\Model\ResourceModel\Collection\Custom::CONDITION_MATCH
        );
    }

    //########################################
}
