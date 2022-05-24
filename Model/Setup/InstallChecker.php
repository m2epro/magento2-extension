<?php

namespace Ess\M2ePro\Model\Setup;

/**
 * Checking if the module is installed
 */
class InstallChecker
{
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;

    /** @var \Ess\M2ePro\Helper\Module\Database\Tables */
    private $tablesHelper;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Ess\M2ePro\Helper\Module\Database\Tables $tablesHelper
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Helper\Module\Database\Tables $tablesHelper
    ) {
        $this->resource = $resource;
        $this->tablesHelper = $tablesHelper;
    }

    /**
     * @return bool
     * @throws \Zend_Db_Statement_Exception
     */
    public function isInstalled(): bool
    {
        $setupTableName = $this->tablesHelper->getFullName('setup');
        if (!$this->getConnection()->isTableExists($setupTableName)) {
            return false;
        }

        $setupRow = $this->getConnection()
                         ->select()
                         ->from($setupTableName)
                         ->where('version_from IS NULL')
                         ->where('is_completed = ?', 1)
                         ->query()
                         ->fetch();

        return $setupRow !== false;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection(): \Magento\Framework\DB\Adapter\AdapterInterface
    {
        return $this->resource->getConnection();
    }
}
