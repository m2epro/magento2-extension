<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;

class Staging extends \Ess\M2ePro\Helper\AbstractHelper
{
    /** @var \Magento\Framework\Module\FullModuleList */
    protected $fullModuleList;

    /** @var \Magento\Framework\App\DeploymentConfig */
    protected $deploymentConfig;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\Module\FullModuleList $fullModuleList,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->fullModuleList = $fullModuleList;
        $this->deploymentConfig = $deploymentConfig;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function isInstalled()
    {
        return $this->fullModuleList->getOne('Magento_CatalogStaging');
    }

    //----------------------------------------

    public function getStagedTables($entityType)
    {
        $tables = [
            $entityType . '_entity_varchar',
            $entityType . '_entity_int',
            $entityType . '_entity_text',
            $entityType . '_entity_datetime',
            $entityType . '_entity_decimal'
        ];

        if ($entityType == ProductAttributeInterface::ENTITY_TYPE_CODE) {
            $tables[] = $entityType . '_entity_gallery';
        }

        return $tables;
    }

    public function isStagedTable($tableName, $entityType = null)
    {
        $tableName = is_array($tableName) ? $tableName[key($tableName)] : $tableName;
        $tableName = str_replace(
            (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX),
            '',
            $tableName
        );

        if (!$entityType) {
            return in_array($tableName, $this->getStagedTables(ProductAttributeInterface::ENTITY_TYPE_CODE)) ||
                   in_array($tableName, $this->getStagedTables(CategoryAttributeInterface::ENTITY_TYPE_CODE));
        }
        return in_array($tableName, $this->getStagedTables($entityType));
    }

    //########################################

    public function getTableLinkField($entityType)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName($entityType . '_entity');

        $indexList = $connection->getIndexList($tableName);
        return $indexList[$connection->getPrimaryKeyName($tableName)]['COLUMNS_LIST'][0];
    }

    //########################################
}