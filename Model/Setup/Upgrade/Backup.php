<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Upgrade;

use Ess\M2ePro\Model\AbstractModel;
use Ess\M2ePro\Model\Exception;
use Ess\M2ePro\Model\Setup\Database\Modifier\Table as TableModifier;
use Magento\Framework\Module\Setup;

class Backup extends AbstractModel
{
    const TABLE_SUFFIX = '__b';
    const TABLE_IDENTIFIER_MAX_LEN = 20;

    private $versionFrom;

    private $versionTo;

    /** @var Setup $installer */
    private $installer;

    /** @var array $tablesList */
    private $tablesList;

    //########################################

    public function __construct(
        $versionFrom, $versionTo,
        Setup $installer,
        array $tablesList,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        if (empty($tablesList)) {
            throw new Exception('Tables list is empty.');
        }

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

            $this->prepareColumns($table);

            $backupTable = $this->getConnection()->createTableByDdl(
                $this->helperFactory->getObject('Module\Database\Tables')->getFullName($table),
                $this->getResultTableName($table)
            );
            $this->getConnection()->createTable($backupTable);

            $select = $this->getConnection()->select()->from(
                $this->helperFactory->getObject('Module\Database\Tables')->getFullName($table)
            );
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
            $this->getConnection()->dropTable(
                $this->helperFactory->getObject('Module\Database\Tables')->getFullName($table)
            );

            $originalTable = $this->getConnection()->createTableByDdl(
                $this->getResultTableName($table),
                $this->helperFactory->getObject('Module\Database\Tables')->getFullName($table)
            );
            $this->getConnection()->createTable($originalTable);

            $select = $this->getConnection()->select()->from($this->getResultTableName($table));
            $this->getConnection()->query(
                $this->getConnection()->insertFromSelect(
                    $select, $this->helperFactory->getObject('Module\Database\Tables')->getFullName($table)
                )
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
        $tableName = $this->helperFactory->getObject('Module\Database\Tables')->getFullName($table)
               .self::TABLE_SUFFIX
               .'_'.str_replace('.', '', $this->versionFrom)
               .'_'.str_replace('.', '', $this->versionTo);

        if (strlen($tableName) > self::TABLE_IDENTIFIER_MAX_LEN) {
            $tableName = 'm2epro' . self::TABLE_SUFFIX .'_'. sha1($tableName);
        }

        return $tableName;
    }

    //########################################

    private function prepareColumns($table)
    {
        $tableInfo = $this->getConnection()->describeTable(
            $this->helperFactory->getObject('Module\Database\Tables')->getFullName($table)
        );

        /** @var \Ess\M2ePro\Model\Setup\Database\Modifier\Table $tableModifier */
        $tableModifier = $this->modelFactory->getObject('Setup\Database\Modifier\Table',
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

        $tableModifier->changeColumn($columnTitle, $columnType, $columnInfo['DEFAULT'], NULL, false);
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

            $tableModifier->changeColumn($columnTitle, $columnType, $columnInfo['DEFAULT'], NULL, false);
        }
    }

    //########################################
}