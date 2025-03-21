<?php

namespace Ess\M2ePro\Model\ChangeTracker\Base;

use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder;
use Ess\M2ePro\Model\ChangeTracker\Common\TemporaryTable;
use Magento\Framework\App\ResourceConnection;

class InventoryStock
{
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder */
    private $queryBuilder;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resource;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    /** @var string */
    private $msiTableName = '';
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger */
    private $logger;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory $queryBuilderFactory
     * @param \Ess\M2ePro\Helper\Magento $magentoHelper
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger
     */
    public function __construct(
        ResourceConnection $resource,
        QueryBuilderFactory $queryBuilderFactory,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger
    ) {
        $this->queryBuilder = $queryBuilderFactory->make();
        $this->resource = $resource;
        $this->magentoHelper = $magentoHelper;
        $this->logger = $logger;
    }

    public function getInventoryStockTableName(TrackerInterface $tracker): string
    {
        if ($this->magentoHelper->isMSISupportingVersion()) {
            return $this->getMsiInventoryTableName($tracker);
        }

        return 'cataloginventory_stock_item';
    }

    public function getInventoryQuery(TrackerInterface $tracker): SelectQueryBuilder
    {
        $stockSubQuery = $this->queryBuilder
            ->makeSubQuery()
            ->addSelect('sku', 'isi.sku')
            ->addSelect('product_id', 'product.entity_id')
            ->addSelect('stock_id', 'issl.stock_id')
            ->addSelect('is_in_stock', 'isi.status')
            ->addSelect('quantity', 'SUM(isi.quantity)')
            ->from('isi', 'inventory_source_item')
            ->leftJoin(
                'source',
                'inventory_source',
                'source.source_code = isi.source_code'
            )
            ->leftJoin(
                'issl',
                'inventory_source_stock_link',
                'isi.source_code = issl.source_code'
            )
            ->leftJoin(
                'product',
                'catalog_product_entity',
                'isi.sku = product.sku'
            )
            ->andWhere('source.enabled = ?', 1)
            ->andWhere('product.entity_id IS NOT NULL')
            ->addGroup('issl.stock_id')
            ->addGroup('isi.sku');

        $reservedSubQuery = $this->queryBuilder
            ->makeSubQuery()
            ->addSelect('stock_id', 'ir.stock_id')
            ->addSelect('product_id', 'cpe.entity_id')
            ->addSelect('sku', 'cpe.sku')
            ->addSelect('quantity', 'SUM(ir.quantity)')
            ->from('ir', 'inventory_reservation')
            ->leftJoin(
                'cpe',
                'catalog_product_entity',
                'cpe.sku = ir.sku'
            )
            ->addGroup('ir.stock_id')
            ->addGroup('cpe.sku');

        return $this->queryBuilder
            ->makeSubQuery()
            ->addSelect('product_id', 'stock.product_id')
            ->addSelect('is_in_stock', 'stock.is_in_stock')
            ->addSelect('qty', 'stock.quantity + IFNULL(reserved.quantity, 0)')
            ->from('stock', $stockSubQuery)
            ->leftJoin(
                'reserved',
                $reservedSubQuery,
                'reserved.sku = stock.sku AND reserved.stock_id = stock.stock_id'
            );
    }

    private function getMsiInventoryTableName(TrackerInterface $tracker): string
    {
        if (
            $this->msiTableName !== ''
            && $this->isTableExists($this->msiTableName)
        ) {
            return $this->msiTableName;
        }

        $inventoryQuery = $this->getInventoryQuery($tracker);
        $this->logger->debug('Stock temporary table', [
            'query' => 'CREATE TEMPORARY TABLE IF NOT EXISTS `tmp_stock` ' . $inventoryQuery->getQuery(),
            'tracker' => $tracker
        ]);

        $start = microtime(true);
        $this->msiTableName = (new TemporaryTable())->createFromSelect(
            $inventoryQuery->getQuery(),
            $this->getDbAdapter()
        );
        $createdTime = (float)number_format(microtime(true) - $start, 4);
        $this->logger->debug("Stock temporary table created in $createdTime seconds", [
            'tracker' => $tracker
        ]);

        return $this->msiTableName;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getDbAdapter(): \Magento\Framework\DB\Adapter\AdapterInterface
    {
        return $this->resource->getConnection();
    }

    /**
     * @param string $tableName
     *
     * @return bool
     */
    private function isTableExists(string $tableName): bool
    {
        try {
            return (bool)$this
                ->getDbAdapter()
                ->select()
                ->from($tableName, new \Zend_Db_Expr('1'))
                ->query();
        } catch (\Magento\Framework\DB\Adapter\TableNotFoundException $exception) {
            return false;
        }
    }
}
