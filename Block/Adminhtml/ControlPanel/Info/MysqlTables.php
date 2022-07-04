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
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->databaseHelper = $databaseHelper;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('controlPanelInfoMysqlTables');
        $this->setTemplate('control_panel/info/mysqlTables.phtml');
    }

    //########################################

    public function getTablesInfo()
    {
        $helper = $this->databaseHelper;
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
