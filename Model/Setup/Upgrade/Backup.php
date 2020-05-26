<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Upgrade;

use Ess\M2ePro\Model\AbstractModel;
use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Model\Setup\Database\Modifier\Table as TableModifier;
use Magento\Framework\Module\Setup;

/**
 * Class \Ess\M2ePro\Model\Setup\Upgrade\Backup
 */
class Backup extends AbstractModel
{
    const TABLE_PREFIX = '__b';

    // max MySQL lenth (64) - backup prefix (m2epro__b_65016_)
    const TABLE_IDENTIFIER_MAX_LEN = 46;

    private $versionFrom;

    private $versionTo;

    /** @var Setup $installer */
    private $installer;

    /** @var array $tablesList */
    private $tablesList = [];

    //########################################

    public function __construct(
        $versionFrom,
        $versionTo,
        Setup $installer,
        array $tablesList,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->versionFrom = $versionFrom;
        $this->versionTo   = $versionTo;

        $this->installer  = $installer;
        $this->tablesList = array_unique($tablesList);

        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function isExists()
    {
        foreach ($this->tablesList as $table) {
            if (!$this->getConnection()->isTableExists($this->getBackupTableName($table))) {
                return false;
            }
        }

        return true;
    }

    // ---------------------------------------

    public function create()
    {
        foreach ($this->tablesList as $table) {
            $this->prepareColumns($table);

            if ($this->getConnection()->isTableExists($this->getBackupTableName($table))) {
                $this->getConnection()->dropTable($this->getBackupTableName($table));
            }

            $backupTable = $this->getConnection()->createTableByDdl(
                $this->getOriginalTableName($table),
                $this->getBackupTableName($table)
            );
            $backupTable->setComment(
                sprintf(
                    'Based on %s. From [%s] to [%s].',
                    $this->getOriginalTableName($table),
                    $this->versionFrom,
                    $this->versionTo
                )
            );
            $this->getConnection()->createTable($backupTable);

            $select = $this->getConnection()->select()->from($this->getOriginalTableName($table));
            $this->getConnection()->query(
                $this->getConnection()->insertFromSelect($select, $this->getBackupTableName($table))
            );
        }
    }

    public function remove()
    {
        foreach ($this->tablesList as $table) {
            if ($this->getConnection()->isTableExists($this->getBackupTableName($table))) {
                $this->getConnection()->dropTable($this->getBackupTableName($table));
            }
        }
    }

    public function rollback()
    {
        foreach ($this->tablesList as $table) {
            if ($this->getConnection()->isTableExists($this->getOriginalTableName($table))) {
                $this->getConnection()->dropTable($this->getOriginalTableName($table));
            }

            $originalTable = $this->getConnection()->createTableByDdl(
                $this->getBackupTableName($table),
                $this->getOriginalTableName($table)
            );
            $this->getConnection()->createTable($originalTable);

            $select = $this->getConnection()->select()->from($this->getBackupTableName($table));
            $this->getConnection()->query(
                $this->getConnection()->insertFromSelect($select, $this->getOriginalTableName($table))
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

    // ---------------------------------------

    public function getOriginalTableName($table)
    {
        return $this->helperFactory->getObject('Module_Database_Tables')->getFullName($table);
    }

    private function getBackupTableName($table)
    {
        $prefix = 'm2epro' . self::TABLE_PREFIX. '_' . str_replace('.', '', $this->versionTo) . '_';

        if (strlen($table) > self::TABLE_IDENTIFIER_MAX_LEN) {
            $table = sha1($table);
        }

        return $prefix . $table;
    }

    //########################################

    private function prepareColumns($table)
    {
        $tableInfo = $this->getConnection()->describeTable(
            $this->helperFactory->getObject('Module_Database_Tables')->getFullName($table)
        );

        /** @var \Ess\M2ePro\Model\Setup\Database\Modifier\Table $tableModifier */
        $tableModifier = $this->modelFactory->getObject(
            'Setup_Database_Modifier_Table',
            [
                'installer' => $this->installer,
                'tableName' => $table,
            ]
        );

        foreach ($tableInfo as $columnTitle => $columnInfo) {
            $this->prepareFloatUnsignedColumns($tableModifier, $columnTitle, $columnInfo);
            $this->prepareVarcharColumns($tableModifier, $columnTitle, $columnInfo);
        }

        $tableModifier->commit();
    }

    /**
     * @param $tableModifier TableModifier
     * @param $columnTitle string
     * @param $columnInfo array
     *
     * convert FLOAT UNSIGNED columns to FLOAT because of zend framework bug in ->createTableByDdl method,
     * that does not support 'FLOAT UNSIGNED' column type
     */
    private function prepareFloatUnsignedColumns(TableModifier $tableModifier, $columnTitle, array $columnInfo)
    {
        if (strtolower($columnInfo['DATA_TYPE']) != 'float unsigned') {
            return;
        }

        $columnType = 'FLOAT';
        if (isset($columnInfo['NULLABLE']) && !$columnInfo['NULLABLE']) {
            $columnType .= ' NOT NULL';
        }

        $tableModifier->changeColumn($columnTitle, $columnType, $columnInfo['DEFAULT'], null, false);
    }

    /**
     * @param $tableModifier TableModifier
     * @param $columnTitle string
     * @param $columnInfo array
     *
     * convert VARCHAR(256-500) to VARCHAR(255) because ->createTableByDdl method will handle this column
     * as TEXT. Due to the incorrect length > 255
     */
    private function prepareVarcharColumns(TableModifier $tableModifier, $columnTitle, array $columnInfo)
    {
        if (strtolower($columnInfo['DATA_TYPE']) != 'varchar') {
            return;
        }

        if ($columnInfo['LENGTH'] > 255 && $columnInfo['LENGTH'] <= 500) {
            $columnType = 'varchar(255)';
            if (isset($columnInfo['NULLABLE']) && !$columnInfo['NULLABLE']) {
                $columnType .= ' NOT NULL';
            }

            $tableModifier->changeColumn($columnTitle, $columnType, $columnInfo['DEFAULT'], null, false);
        }
    }

    //########################################
}
