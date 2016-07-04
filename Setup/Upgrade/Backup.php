<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade;

use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Setup\Tables;
use Magento\Framework\Module\Setup;

class Backup
{
    const BACKUP_TABLE_SUFFIX = '__backup';

    protected $versionFrom;

    protected $versionTo;

    /** @var array $tablesList */
    protected $tablesList;

    /** @var Setup $installer */
    protected $installer;

    /** @var Tables $tablesObject */
    protected $tablesObject;

    //########################################

    public function __construct(
        $versionFrom, $versionTo,
        array $tablesList,
        Setup $installer,
        Tables $tablesObject
    ) {
        if (empty($tablesList)) {
            throw new Exception('Tables list is empty.');
        }

        $this->versionFrom = $versionFrom;
        $this->versionTo   = $versionTo;

        $this->tablesList = $tablesList;

        $this->installer    = $installer;
        $this->tablesObject = $tablesObject;
    }

    //########################################

    public function isExists()
    {
        foreach ($this->tablesList as $table) {
            if (!$this->getConnection()->isTableExists($this->getResultTableName($table))) {
                return false;
            }
        }

        return true;
    }

    // ---------------------------------------

    public function create()
    {
        foreach ($this->tablesList as $table) {
            $backupTable = $this->getConnection()->createTableByDdl(
                $this->tablesObject->getFullName($table), $this->getResultTableName($table)
            );
            $this->getConnection()->createTable($backupTable);

            $select = $this->getConnection()->select()->from($this->tablesObject->getFullName($table));
            $this->getConnection()->query(
                $this->getConnection()->insertFromSelect($select, $this->getResultTableName($table))
            );
        }
    }

    public function remove()
    {
        foreach ($this->tablesList as $table) {
            $this->getConnection()->dropTable($this->getResultTableName($table));
        }
    }

    public function rollback()
    {
        foreach ($this->tablesList as $table) {
            $this->getConnection()->dropTable($this->tablesObject->getFullName($table));

            $backupTable = $this->getConnection()->createTableByDdl(
                $this->getResultTableName($table), $this->tablesObject->getFullName($table)
            );
            $this->getConnection()->createTable($backupTable);

            $select = $this->getConnection()->select()->from($this->getResultTableName($table));
            $this->getConnection()->query(
                $this->getConnection()->insertFromSelect($select, $this->tablesObject->getFullName($table))
            );
        }
    }

    //########################################

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection()
    {
        return $this->installer->getConnection();
    }

    private function getResultTableName($table)
    {
        return $this->tablesObject->getFullName($table)
               .self::BACKUP_TABLE_SUFFIX
               .'_v'.str_replace('.', '_', $this->versionFrom)
               .'_v'.str_replace('.', '_', $this->versionTo);
    }

    //########################################
}