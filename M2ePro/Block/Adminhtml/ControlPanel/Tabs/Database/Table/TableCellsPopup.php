<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database\Table;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Tabs\Database\Table\TableCellsPopup
 */
class TableCellsPopup extends AbstractBlock
{
    const MODE_CREATE = 'create';
    const MODE_UPDATE = 'update';

    private $tableName;
    private $mode    = self::MODE_UPDATE;
    private $rowsIds = [];

    /** @var  \Ess\M2ePro\Model\ControlPanel\Database\TableModel */
    public $tableModel;
    private $databaseTableFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Model\ControlPanel\Database\TableModelFactory $databaseTableFactory,
        array $data = []
    ) {
        $this->databaseTableFactory = $databaseTableFactory;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('controlPanelDatabaseTableCellsPopup');
        // ---------------------------------------

        $this->setTemplate('control_panel/tabs/database/table_cells_popup.phtml');

        $this->init();
    }

    private function init()
    {
        $this->tableName = $this->getRequest()->getParam('table');
        $this->mode      = $this->getRequest()->getParam('mode');
        $this->rowsIds   = explode(',', $this->getRequest()->getParam('ids'));

        $component = $this->getRequest()->getParam('component');
        $mergeMode = (bool)$this->getRequest()->getParam('merge', false);

        /** @var \Ess\M2ePro\Model\ControlPanel\Database\TableModel $model */
        $model = $this->databaseTableFactory->create(['data' => [
            'table_name' => $this->tableName,
            'merge_mode' => $mergeMode,
            'merge_mode_component' => $component
        ]]);

        $this->tableModel = $model;
    }

    //########################################

    public function isUpdateCellsMode()
    {
        return $this->mode == self::MODE_UPDATE;
    }

    //########################################

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getIds()
    {
        return $this->rowsIds;
    }

    //########################################
}
