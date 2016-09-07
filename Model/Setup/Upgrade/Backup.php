<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Upgrade;

use Ess\M2ePro\Model\AbstractModel;
use Ess\M2ePro\Model\Exception;
use Magento\Framework\Module\Setup;

class Backup extends AbstractModel
{
    const TABLE_SUFFIX = '__backup';

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
        return $this->helperFactory->getObject('Module\Database\Tables')->getFullName($table)
               .self::TABLE_SUFFIX
               .'_v'.str_replace('.', '_', $this->versionFrom)
               .'_v'.str_replace('.', '_', $this->versionTo);
    }

    //########################################
}