<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Setup\Upgrade\Entity;

use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Model\Factory as ModelFactory;
use Ess\M2ePro\Model\Setup\Database\Modifier\Table;
use Ess\M2ePro\Model\Setup\Database\Modifier\Config;
use Magento\Framework\Module\Setup;

abstract class AbstractFeature
{
    protected $helperFactory;

    protected $modelFactory;

    protected $installer;

    //########################################

    public function __construct(
        HelperFactory $helperFactory,
        ModelFactory $modelFactory,
        Setup $installer
    ) {
        $this->helperFactory = $helperFactory;
        $this->modelFactory  = $modelFactory;
        $this->installer     = $installer;
    }

    //########################################

    /**
     * @return array
     */
    public function getBackupTables()
    {
        return [];
    }

    abstract public function execute();

    //########################################

    /**
     * @param $tableName
     * @return Table
     */
    protected function getTableModifier($tableName)
    {
        return $this->modelFactory->getObject('Setup\Database\Modifier\Table',
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

        return $this->modelFactory->getObject('Setup\Database\Modifier\Config',
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

    protected function getFullTableName($tableName)
    {
        return $this->helperFactory->getObject('Module\Database\Tables')->getFullName($tableName);
    }

    //########################################
}