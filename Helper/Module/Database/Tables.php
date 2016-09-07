<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Database;

use Ess\M2ePro\Helper\AbstractHelper;
use Ess\M2ePro\Helper\Factory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;

class Tables extends AbstractHelper
{
    const PREFIX = 'm2epro_';

    /** @var ResourceConnection */
    protected $resourceConnection;

    //########################################

    public function __construct(
        ResourceConnection $resourceConnection,
        Factory $helperFactory,
        Context $context
    ) {
        $this->resourceConnection  = $resourceConnection;

        parent::__construct($helperFactory, $context);
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
        return $this->resourceConnection->getConnection()->isTableExists($this->getFullName($tableName));
    }

    public function getFullName($tableName)
    {
        return $this->resourceConnection->getTableName(self::PREFIX.$tableName);
    }

    //########################################
}