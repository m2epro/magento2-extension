<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Upgrade\Source;

use Ess\M2ePro\Setup\Tables;
use Ess\M2ePro\Setup\Modifier\Table;
use Ess\M2ePro\Setup\Modifier\TableFactory;
use Ess\M2ePro\Setup\Modifier\Config;
use Ess\M2ePro\Setup\Modifier\ConfigFactory;
use Magento\Framework\Module\Setup;

abstract class AbstractFeature
{
    protected $installer;

    protected $tablesObject;

    protected $tableModifierFactory;

    protected $configModifierFactory;

    //########################################

    public function __construct(
        Setup $installer,
        Tables $tablesObject,
        TableFactory $tableModifierFactory,
        ConfigFactory $configModifierFactory
    ) {
        $this->installer = $installer;
        $this->tablesObject = $tablesObject;
        $this->tableModifierFactory = $tableModifierFactory;
        $this->configModifierFactory = $configModifierFactory;
    }

    //########################################

    /**
     * @return array
     */
    abstract public function getBackupTables();

    abstract public function execute();

    //########################################

    /**
     * @param $tableName
     * @return Table
     */
    protected function getTableModifier($tableName)
    {
        return $this->tableModifierFactory->create(
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    /**
     * @param $configName
     * @return Config
     */
    protected function getConfigModifier($configName)
    {
        $tableName = $configName.'_config';

        return $this->configModifierFactory->create(
            [
                'installer' => $this->installer,
                'tableName' => $tableName,
            ]
        );
    }

    //########################################

    /**
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql
     */
    protected function getConnection()
    {
        return $this->installer->getConnection();
    }

    //########################################
}