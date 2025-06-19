<?php

namespace Ess\M2ePro\Model\ChangeTracker\Base;

use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder;

class InventoryStock
{
    private SelectQueryBuilder $queryBuilder;
    private \Ess\M2ePro\Helper\Magento $magentoHelper;

    public function __construct(
        QueryBuilderFactory $queryBuilderFactory,
        \Ess\M2ePro\Helper\Magento $magentoHelper
    ) {
        $this->queryBuilder = $queryBuilderFactory->make();
        $this->magentoHelper = $magentoHelper;
    }

    public function getInventoryStockQuery(TrackerInterface $tracker): SelectQueryBuilder
    {
        if ($this->magentoHelper->isMSISupportingVersion()) {
            return $this->getMsiInventoryQuery($tracker);
        }

        return $this->getNoMsiInventoryQuery($tracker);
    }

    public function getInventoryStockJoinCondition(): string
    {
        if ($this->magentoHelper->isMSISupportingVersion()) {
            return 'product.product_id = stock.product_id AND product.store_id = stock.store_id';
        }

        return 'product.product_id = stock.product_id';
    }

    private function getMsiInventoryQuery(TrackerInterface $tracker): SelectQueryBuilder
    {
        $stockSubQuery = $this->queryBuilder
            ->makeSubQuery()
            ->addSelect('sku', 'isi.sku')
            ->addSelect('product_id', 'product.entity_id')
            ->addSelect('stock_id', 'issl.stock_id')
            ->addSelect('is_in_stock', 'MAX(isi.status)')
            ->addSelect('quantity', 'SUM(isi.quantity)')
            ->from('isi', 'inventory_source_item')
            ->leftJoin(
                'source',
                'inventory_source',
                'source.source_code = isi.source_code'
            )
            ->innerJoin(
                'issl',
                'inventory_source_stock_link',
                'isi.source_code = issl.source_code'
            )
            ->innerJoin(
                'product',
                'catalog_product_entity',
                'isi.sku = product.sku'
            )
            ->andWhere('source.enabled = ?', 1)
            ->andWhere('product.entity_id IN (?)', $tracker->getMagentoProductIds())
            ->addGroup('isi.sku')
            ->addGroup('product.entity_id')
            ->addGroup('issl.stock_id');

        $reservedSubQuery = $this->queryBuilder
            ->makeSubQuery()
            ->addSelect('stock_id', 'ir.stock_id')
            ->addSelect('sku', 'ir.sku')
            ->addSelect('quantity', 'SUM(ir.quantity)')
            ->from('ir', 'inventory_reservation')
            ->addGroup('ir.stock_id')
            ->addGroup('ir.sku');

        return $this->queryBuilder
            ->makeSubQuery()
            ->addSelect('product_id', 'stock.product_id')
            ->addSelect('store_id', 'st.store_id')
            ->addSelect('is_in_stock', 'stock.is_in_stock')
            ->addSelect('qty', 'stock.quantity + IFNULL(reserved.quantity, 0)')
            ->from('stock', $stockSubQuery)
            ->leftJoin(
                'reserved',
                $reservedSubQuery,
                'reserved.sku = stock.sku AND reserved.stock_id = stock.stock_id'
            )
            ->leftJoin(
                's',
                'inventory_stock',
                's.stock_id = stock.stock_id'
            )
            ->leftJoin(
                'issc',
                'inventory_stock_sales_channel',
                's.stock_id = issc.stock_id'
            )
            ->innerJoin(
                'sw',
                'store_website',
                "issc.code = sw.code AND issc.type = 'website'"
            )
            ->innerJoin(
                'st',
                'store',
                'sw.website_id = st.website_id'
            );
    }

    private function getNoMsiInventoryQuery(TrackerInterface $tracker): SelectQueryBuilder
    {
        return $this->queryBuilder
            ->makeSubQuery()
            ->addSelect('product_id', 'stock.product_id')
            ->addSelect('is_in_stock', 'stock.is_in_stock')
            ->addSelect('qty', 'stock.qty')
            ->from('stock', 'cataloginventory_stock_item')
            ->andWhere('stock.product_id IN (?)', $tracker->getMagentoProductIds())
            ->andWhere('stock.stock_id = 1')
            ->andWhere('stock.website_id = 0')
        ;
    }
}
