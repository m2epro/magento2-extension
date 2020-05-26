<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Checker\Task;

/**
 * Class \Ess\M2ePro\Model\Cron\Checker\Task\RepairCrashedTables
 */
class RepairCrashedTables extends \Ess\M2ePro\Model\Cron\Checker\Task\AbstractModel
{
    const NICK = 'repair_crashed_tables';

    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @return array
     */
    protected function getTablesForCheck()
    {
        return [
            'm2epro_operation_history',
            'm2epro_listing_log',
            'm2epro_order_log',
            'm2epro_synchronization_log',
            'm2epro_lock_item',
            'm2epro_processing_lock'
        ];
    }

    //########################################

    public function performActions()
    {
        $crashedTables = $this->getCrashedTablesFromLog();

        $workingTables = $this->getAlreadyRepairedTables($crashedTables);
        $this->performActionsToAlreadyWorkingTables($workingTables);

        $tablesForRepair = array_diff($crashedTables, $workingTables);
        $tablesForRepair = $this->filterAlreadyTriedToRepairTables($tablesForRepair);

        if (empty($tablesForRepair)) {
            return;
        }

        $this->processRepairTables($tablesForRepair);
    }

    //########################################

    /**
     * @return array
     */
    protected function getCrashedTablesFromLog()
    {
        $crashedTables = [];

        foreach ($this->getCrashedTablesLogRecords() as $row) {
            $table = $this->findTableNameInLog($row['description']);
            $table !== null && $crashedTables[] = $table;
        }

        return array_unique($crashedTables);
    }

    protected function processRepairTables($tablesForRepair)
    {
        foreach ($tablesForRepair as $table) {
            if (!$this->getHelper('Module_Database_Structure')->isTableExists($table)) {
                continue;
            }

            if (!$this->getHelper('Module_Database_Repair')->repairCrashedTable($table)) {
                $this->setTryToRepair($table);
            }
        }
    }

    //########################################

    /**
     * @param array $tables
     * @return array
     */
    protected function getAlreadyRepairedTables(array $tables)
    {
        $workingTables = [];
        foreach ($tables as $table) {
            $this->getHelper('Module_Database_Structure')->isTableStatusOk($table) && $workingTables[] = $table;
        }

        return $workingTables;
    }

    /**
     * @param array $tables
     */
    protected function performActionsToAlreadyWorkingTables($tables)
    {
        foreach ($tables as $table) {
            $this->unsetTryToRepair($table);
        }
    }

    //########################################

    /**
     * @param array $tables
     * @return array
     */
    protected function filterAlreadyTriedToRepairTables($tables)
    {
        $filteredTables = [];
        foreach ($tables as $table) {
            !$this->wasTryToRepair($table) && $filteredTables[] = $table;
        }
        return $filteredTables;
    }

    //########################################

    /**
     * @return array
     */
    protected function getCrashedTablesLogRecords()
    {
        $readConnection = $this->resourceConnection->getConnection();

        $currentDate = $this->getHelper('Data')->getCurrentGmtDate();
        $dateTimeTo = new \DateTime($currentDate, new \DateTimeZone('UTC'));

        $dateTimeFrom = new \DateTime($currentDate, new \DateTimeZone('UTC'));
        $dateTimeFrom->modify('-1 hour');

        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_system_log');
        $select = $this->resourceConnection->getConnection()->select()
            ->from($tableName, ['id', 'type', 'description', 'create_date'])
            ->where($readConnection->quoteInto('`type` = ?', 'Zend_Db_Statement_Exception'))
            ->where($readConnection->quoteInto('`create_date` >= ?', $dateTimeFrom->format('Y-m-d H:i:s')))
            ->where($readConnection->quoteInto('`create_date` <= ?', $dateTimeTo->format('Y-m-d H:i:s')))
            ->where('`description` REGEXP "General error\: (126|127|132|134|141|144|145)"');

        return $select->query()->fetchAll();
    }

    /**
     * @param string $description
     * @return null|string
     */
    protected function findTableNameInLog($description)
    {
        $tablesForPattern = implode('|', $this->getTablesForCheck());
        $pattern = '/`(\w{1,})?('.$tablesForPattern.')\`/i';
        preg_match($pattern, $description, $matches);

        if (isset($matches[2])) {
            return $matches[2];
        }

        return null;
    }

    //########################################

    protected function setTryToRepair($table)
    {
        $this->getHelper('Module')->getCacheConfig()
            ->setGroupValue('/cron/repair_tables/'.$table.'/', 'tried_to_repair', 1);
    }

    protected function unsetTryToRepair($table)
    {
        $cacheConfig = $this->getHelper('Module')->getCacheConfig();
        if ($cacheConfig->getGroupValue('/cron/repair_tables/'.$table.'/', 'tried_to_repair')) {
            $cacheConfig->deleteGroupValue('/cron/repair_tables/'.$table.'/', 'tried_to_repair');
        }
    }

    /**
     * @param string $table
     * @return bool
     */
    protected function wasTryToRepair($table)
    {
        return (bool)$this->getHelper('Module')->getCacheConfig()
            ->getGroupValue('/cron/repair_tables/'.$table.'/', 'tried_to_repair');
    }

    //########################################
}
