<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Database;

use Magento\Catalog\Api\Data\ProductAttributeInterface;

class Tables
{
    public const PREFIX = 'm2epro_';

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var \Ess\M2ePro\Helper\Magento\Staging */
    private $stagingHelper;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper
     * @param \Ess\M2ePro\Helper\Magento\Staging $stagingHelper
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Magento\Staging $stagingHelper
    ) {
        $this->resourceConnection  = $resourceConnection;
        $this->databaseHelper = $databaseHelper;
        $this->stagingHelper = $stagingHelper;
    }

    /**
     * @param string $tableName
     *
     * @return bool
     */
    public function isExists(string $tableName): bool
    {
        return $this->resourceConnection
            ->getConnection()
            ->isTableExists($this->getFullName($tableName));
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    public function getFullName(string $tableName): string
    {
        if (strpos($tableName, self::PREFIX) === false) {
            $tableName = self::PREFIX . $tableName;
        }

        return $this->databaseHelper->getTableNameWithPrefix($tableName);
    }

    /**
     * @param string $oldTable
     * @param string $newTable
     *
     * @return bool
     */
    public function renameTable(string $oldTable, string $newTable): bool
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
     *
     * @return string
     */
    public function normalizeEavColumn($table, string $columnName): string
    {
        if ($this->stagingHelper->isInstalled() &&
            $this->stagingHelper->isStagedTable($table, ProductAttributeInterface::ENTITY_TYPE_CODE) &&
            strpos($columnName, 'entity_id') !== false) {
            $columnName = str_replace(
                'entity_id',
                $this->stagingHelper->getTableLinkField(ProductAttributeInterface::ENTITY_TYPE_CODE),
                $columnName
            );
        }

        return $columnName;
    }
}
