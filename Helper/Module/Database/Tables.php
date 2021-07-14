<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Database;

use Ess\M2ePro\Helper\AbstractHelper;
use Ess\M2ePro\Helper\Factory;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;

/**
 * Class \Ess\M2ePro\Helper\Module\Database\Tables
 */
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

    public function isExists($tableName)
    {
        return $this->resourceConnection->getConnection()->isTableExists($this->getFullName($tableName));
    }

    public function getFullName($tableName)
    {
        if (strpos($tableName, self::PREFIX) === false) {
            $tableName = self::PREFIX . $tableName;
        }

        return $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix($tableName);
    }

    //########################################

    public function renameTable($oldTable, $newTable)
    {
        $oldTable = $this->getFullName($oldTable);
        $newTable = $this->getFullName($newTable);

        if ($this->resourceConnection->getConnection()->isTableExists($oldTable) &&
            !$this->resourceConnection->getConnection()->isTableExists($newTable)) {
            $this->resourceConnection->getConnection()->renameTable(
                $oldTable,
                $newTable
            );
            return true;
        }

        return false;
    }

    /**
     * @param array|string $table
     * @param string $columnName
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function normalizeEavColumn($table, $columnName)
    {
        /** @var \Ess\M2ePro\Helper\Magento\Staging $helper */
        $helper = $this->helperFactory->getObject('Magento\Staging');

        if ($helper->isInstalled() &&
            $helper->isStagedTable($table, ProductAttributeInterface::ENTITY_TYPE_CODE) &&
            strpos($columnName, 'entity_id') !== false) {
            $columnName = str_replace(
                'entity_id',
                $helper->getTableLinkField(ProductAttributeInterface::ENTITY_TYPE_CODE),
                $columnName
            );
        }

        return $columnName;
    }

    //########################################
}
