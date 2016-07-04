<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup;

use Ess\M2ePro\Helper\Factory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Module\Setup;

class Tables
{
    const M2E_PRO_TABLE_PREFIX = 'm2epro_';

    /** @var Setup */
    protected $installer;

    /** @var AdapterInterface */
    protected $connection;

    protected $helperFactory;

    /**
     * @var string[]
     */
    private $entities = array();

    //########################################

    public function __construct(
        Setup $installer,
        Factory $helperFactory
    ) {
        $this->installer  = $installer;
        $this->connection = $installer->getConnection();

        $this->helperFactory = $helperFactory;
    }

    //########################################

    public function getCurrentEntities()
    {
        $result = array();
        $currentTables = $this->helperFactory->getObject('Module\Database\Structure')->getMySqlTables();

        foreach ($currentTables as $table) {
            $result[$table] = $this->getFullName($table);
        }

        return $result;
    }

    // ---------------------------------------

    public function getCurrentConfigEntities()
    {
        $result = array();

        $currentConfigTables = array(
            'primary_config',
            'module_config',
            'cache_config',
            'synchronization_config'
        );

        foreach ($currentConfigTables as $table) {
            $result[$table] = $this->getFullName($table);
        }

        return $result;
    }

    //########################################

    public function isExists($tableName)
    {
        return $this->installer->tableExists($this->getFullName($tableName));
    }

    public function getFullName($tableName)
    {
        if (isset($this->entities[$tableName])) {
            return $this->entities[$tableName];
        }

        return $this->entities[$tableName] = $this->installer->getTable(self::M2E_PRO_TABLE_PREFIX.$tableName);
    }

    //########################################
}