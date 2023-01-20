<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common;

use Magento\Framework\DB\TemporaryTableService;

class TemporaryTable
{
    /** @var \Magento\Framework\DB\TemporaryTableService */
    private $tableService;

    public function __construct()
    {
        $this->tableService = new TemporaryTableService(
            new \Magento\Framework\Math\Random(),
            [TemporaryTableService::INDEX_METHOD_HASH],
            [TemporaryTableService::DB_ENGINE_INNODB]
        );
    }

    /**
     * @param \Magento\Framework\DB\Select $selectQuery
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     *
     * @return string temporary table name
     */
    public function createFromSelect(
        \Magento\Framework\DB\Select $selectQuery,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection
    ): string {
        return $this->tableService->createFromSelect(
            $selectQuery,
            $connection
        );
    }
}
