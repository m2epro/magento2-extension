<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\ControlPanel\Info;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;
use Ess\M2ePro\Helper\Module\Database\Structure;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\ControlPanel\Info\MysqlTables
 * @method getTablesList()
 */
class MysqlTables extends AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelInfoMysqlTables');
        $this->setTemplate('control_panel/info/mysqlTables.phtml');
    }

    //########################################

    public function getTablesInfo()
    {
        /** @var Structure $helper */
        $helper = $this->getHelper('Module_Database_Structure');
        $tablesInfo = [];

        foreach ($this->getTablesList() as $category => $tables) {
            foreach ($tables as $tableName) {
                $tablesInfo[$category][$tableName] = [
                    'count' => 0, 'url'   => '#'
                ];

                if (!$helper->isTableReady($tableName)) {
                    continue;
                }

                $tablesInfo[$category][$tableName]['count'] = $helper->getCountOfRecords($tableName);
                $tablesInfo[$category][$tableName]['url'] = $this->getUrl(
                    '*/controlPanel_database/manageTable',
                    ['table' => $tableName]
                );
            }
        }

        return $tablesInfo;
    }

    //########################################
}
