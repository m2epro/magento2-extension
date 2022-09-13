<?php

namespace Ess\M2ePro\Model\ChangeTracker\Base;

use Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder;

abstract class BaseInventoryTracker implements TrackerInterface
{
    /** @var string */
    private $channel;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder */
    protected $queryBuilder;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Base\InventoryStock */
    protected $inventoryStock;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger */
    protected $logger;

    /**
     * @param string $channel
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory $queryBuilderFactory
     * @param \Ess\M2ePro\Model\ChangeTracker\Base\InventoryStock $inventoryStock
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger
     */
    public function __construct(
        string $channel,
        QueryBuilderFactory $queryBuilderFactory,
        InventoryStock $inventoryStock,
        TrackerLogger $logger
    ) {
        $this->channel = $channel;
        $this->inventoryStock = $inventoryStock;
        $this->queryBuilder = $queryBuilderFactory->make();
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return TrackerInterface::TYPE_INVENTORY;
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @return \Magento\Framework\DB\Select
     */
    public function getDataQuery(): \Magento\Framework\DB\Select
    {
        $query = $this->getSelectQuery();

        $mainQuery = $this->queryBuilder
            ->makeSubQuery()
            ->distinct()
            ->addSelect('listing_product_id', 'base.listing_product_id')
            ->from('base', $query)
        ;

        $modeExpression = "(CASE base.status
        WHEN 2 THEN  IF(base.is_in_stock = 0 OR base.calculated_qty = 0, 'stop', 'revise')
        WHEN 0 THEN  'list'
        ELSE 'relist'
        END)";
        $mainQuery->addSelect('mode', $modeExpression);

        $condition = 'base.online_qty != base.calculated_qty';

        $mainQuery->andWhere($condition);

        $message = sprintf(
            'Data query %s %s',
            $this->getType(),
            $this->getChannel()
        );
        $this->logger->debug($message, [
            'query' => (string) $mainQuery->getQuery(),
            'type' => $this->getType(),
            'channel' => $this->getChannel(),
        ]);

        return $mainQuery->getQuery();
    }

    /**
     * Base product sub query.
     * Includes all necessary information regarding the listing product
     * @return \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
     */
    protected function productSubQuery(): SelectQueryBuilder
    {
        $query = $this->queryBuilder->makeSubQuery();
        $query->distinct();

        $listingProductIdExpression = 'lp.id';
        $query->addSelect('listing_product_id', $listingProductIdExpression);

        $productIdExpression = 'IFNULL(lpvo.product_id, lp.product_id)';
        $query->addSelect('product_id', $productIdExpression);

        $query
            ->addSelect('status', 'lp.status')
            ->addSelect('store_id', 'l.store_id')
            ->addSelect('sync_template_id', 'c_l.template_synchronization_id')
            ->addSelect('selling_template_id', 'c_l.template_selling_format_id')
            ->addSelect('is_variation', 'IFNULL(c_lp.is_variation_parent, 0)')
        ;

        $onlineQtyExpression = 'IFNULL(c_lp.online_qty, 0)';
        $query->addSelect('online_qty', $onlineQtyExpression);

        $query
            ->from(
                'c_lp',
                $this->setChannelToTableName('m2epro_%s_listing_product')
            )
            ->innerJoin(
                'lp',
                'm2epro_listing_product',
                'lp.id = c_lp.listing_product_id'
            )
            ->innerJoin(
                'l',
                'm2epro_listing',
                'lp.listing_id = l.id'
            )
            ->innerJoin(
                'c_l',
                $this->setChannelToTableName('m2epro_%s_listing'),
                'c_l.listing_id = lp.listing_id'
            )
            ->leftJoin(
                'lpv',
                'm2epro_listing_product_variation',
                'lpv.listing_product_id = lp.id'
            )
            ->leftJoin(
                'lpvo',
                'm2epro_listing_product_variation_option',
                'lpvo.listing_product_variation_id = lpv.id'
            )
        ;

        return $query;
    }

    /**
     * @return SelectQueryBuilder
     */
    protected function sellingPolicySubQuery(): SelectQueryBuilder
    {
        return $this->queryBuilder
            ->makeSubQuery()
            ->addSelect('template_selling_format_id', 'sp.template_selling_format_id')
            ->addSelect('mode', 'sp.qty_mode')
            ->addSelect('percentage', 'sp.qty_percentage')
            ->addSelect('custom_value', 'sp.qty_custom_value')
            ->addSelect('custom_attribute', 'sp.qty_custom_attribute')
            ->addSelect('custom_attribute_default_value', 'CAST(ea.default_value AS UNSIGNED)')
            ->addSelect('conditional_quantity', 'sp.qty_modification_mode')
            ->addSelect('min_qty', 'sp.qty_min_posted_value')
            ->addSelect('max_qty', 'sp.qty_max_posted_value')
            ->from(
                'sp',
                $this->setChannelToTableName('m2epro_%s_template_selling_format')
            )
            ->leftJoin(
                'ea',
                'eav_attribute',
                'ea.attribute_code = sp.qty_custom_attribute'
            )
        ;
    }

    /**
     * @return SelectQueryBuilder
     */
    protected function synchronizationPolicySubQuery(): SelectQueryBuilder
    {
        return $this->queryBuilder
            ->makeSubQuery()
            ->addSelect('template_synchronization_id', 'ts.template_synchronization_id')
            ->addSelect(
                'revise_threshold',
                'IF(
                            ts.revise_update_qty_max_applied_value_mode = 1,
                            ts.revise_update_qty_max_applied_value,
                            999999
                        )'
            )
            ->from(
                'ts',
                $this->setChannelToTableName('m2epro_%s_template_synchronization')
            )
        ;
    }

    /**
     * @inheridoc
     */
    protected function getSelectQuery(): SelectQueryBuilder
    {
        $query = $this->queryBuilder;

        /* Selects */
        $query
            ->addSelect('listing_product_id', 'product.listing_product_id')
            ->addSelect('product_id', 'product.product_id')
            ->addSelect('status', 'product.status')
            ->addSelect('store_id', 'product.store_id')
            ->addSelect('sync_template_id', 'product.sync_template_id')
            ->addSelect('selling_template_id', 'product.selling_template_id')
            ->addSelect('is_in_stock', 'stock.is_in_stock')
            ->addSelect('stock_qty', 'FLOOR(stock.qty)')
            ->addSelect('online_qty', 'product.online_qty')
            ->addSelect('is_variation', 'product.is_variation')
            ->addSelect('revise_threshold', 'sync_policy.revise_threshold')
        ;

        $query->addSelect('calculated_qty', $this->calculatedQtyExpression());

        /* Tables */
        $query
            ->from('product', $this->productSubQuery())
            ->innerJoin(
                'stock',
                $this->inventoryStock->getInventoryStockTableName(),
                'product.product_id = stock.product_id'
            )
            ->leftJoin(
                'selling_policy',
                $this->sellingPolicySubQuery(),
                'selling_policy.template_selling_format_id = product.selling_template_id'
            )
            ->leftJoin(
                'sync_policy',
                $this->synchronizationPolicySubQuery(),
                'sync_policy.template_synchronization_id = product.sync_template_id'
            )
            ->leftJoin(
                'ea',
                'eav_attribute',
                'ea.attribute_code = selling_policy.custom_attribute'
            )
        ;

        $entityVarcharCondition = '
            cpev.attribute_id = ea.attribute_id
            AND cpev.entity_id = product.product_id
            AND cpev.store_id = product.store_id
        ';
        $query->leftJoin(
            'cpev',
            'catalog_product_entity_varchar',
            $entityVarcharCondition
        );

        $query
            ->leftJoin(
                'pl',
                'm2epro_processing_lock',
                'pl.object_id = product.listing_product_id'
            )
            ->leftJoin(
                'lpsa',
                'm2epro_listing_product_scheduled_action',
                'lpsa.listing_product_id = product.listing_product_id'
            )
            ->leftJoin(
                'lpi',
                'm2epro_listing_product_instruction',
                'lpi.listing_product_id = product.listing_product_id'
            )
        ;

        $query
            ->andWhere('pl.object_id IS NULL')
            ->andWhere('lpsa.listing_product_id IS NULL')
            ->andWhere('lpi.listing_product_id IS NULL')
        ;

        //$query
        //    ->addGroup('product.listing_product_id')
        //    ->addGroup('product.product_id')
        //    ->addGroup('product.store_id')
        //    ->addGroup('product.sync_template_id')
        //    ->addGroup('product.selling_template_id')
        //    ->addGroup('stock.is_in_stock')
        //    ->addGroup('FLOOR(stock.qty)')
        //    ->addGroup('product.online_qty')
        //;

        return $query;
    }

    /**
     * @param string $tableName
     *
     * @return string
     */
    protected function setChannelToTableName(string $tableName): string
    {
        return sprintf($tableName, $this->getChannel());
    }

    /**
     * @return string
     */
    protected function calculatedQtyExpression(): string
    {
        $calcQty = '
            (CASE
                WHEN selling_policy.mode IN (2, 3)
                    THEN selling_policy.custom_value
                WHEN selling_policy.mode IN (1, 5)
                    THEN stock.qty * selling_policy.percentage / 100
                WHEN selling_policy.mode = 4
                    THEN IF(
                        cpev.value IS NULL,
                        selling_policy.custom_attribute_default_value,
                        CAST(cpev.value AS DECIMAL(10, 0))
                    ) * selling_policy.percentage / 100
            END)
        ';

        return "FLOOR(CASE
                WHEN conditional_quantity = 1 AND $calcQty > selling_policy.max_qty
                    THEN selling_policy.max_qty
                ELSE $calcQty
            END
        )";
    }
}
