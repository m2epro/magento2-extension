<?php

namespace Ess\M2ePro\Model\ChangeTracker\Base;

use Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder;

abstract class BaseInventoryTracker implements TrackerInterface
{
    /** @var string */
    private $channel;
    /** @var SelectQueryBuilder */
    protected $queryBuilder;
    /** @var InventoryStock */
    protected $inventoryStock;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger */
    protected $logger;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder */
    private $attributesQueryBuilder;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\BlockingErrorConfig */
    private $blockingErrorConfig;

    public function __construct(
        string $channel,
        QueryBuilderFactory $queryBuilderFactory,
        InventoryStock $inventoryStock,
        ProductAttributesQueryBuilder $attributesQueryBuilder,
        TrackerLogger $logger,
        \Ess\M2ePro\Helper\Component\Ebay\BlockingErrorConfig $blockingErrorConfig
    ) {
        $this->channel = $channel;
        $this->inventoryStock = $inventoryStock;
        $this->queryBuilder = $queryBuilderFactory->make();
        $this->logger = $logger;
        $this->attributesQueryBuilder = $attributesQueryBuilder;
        $this->blockingErrorConfig = $blockingErrorConfig;
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
            ->from('base', $query);

        $isMeetChangeQty = '
            (base.calculated_qty > online_qty AND online_qty < revise_threshold)
            OR (base.calculated_qty != online_qty) AND (base.calculated_qty < base.revise_threshold)
        ';

        $mainQuery->andWhere($isMeetChangeQty);

        $isMeetStop = '
            base.status = 2 AND (
                (base.stop_when_product_disabled AND base.product_disabled)
                OR (base.stop_when_product_out_of_stock AND base.is_in_stock = 0)
                OR (base.stop_when_qty_less_than >= base.stock_qty)
            )
        ';
        $mainQuery->orWhere($isMeetStop);

        $isMeetRelist = '
           base.status IN (1, 3, 4, 5) AND (
                (base.stock_qty >= base.relist_when_qty_more_or_equal)
                AND (base.relist_when_product_is_in_stock AND base.is_in_stock = 1)
                AND (base.relist_when_product_status_enabled AND NOT base.product_disabled)
            )
        ';
        $mainQuery->orWhere($isMeetRelist);

        $message = sprintf(
            'Data query %s %s',
            $this->getType(),
            $this->getChannel()
        );
        $this->logger->debug($message, [
            'query' => (string)$mainQuery->getQuery(),
            'type' => $this->getType(),
            'channel' => $this->getChannel(),
        ]);

        return $mainQuery->getQuery();
    }

    /**
     * Base product sub query.
     * Includes all necessary information regarding the listing product
     * @return SelectQueryBuilder
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
            ->addSelect('is_variation', 'IFNULL(c_lp.is_variation_parent, 0)');

        $query->addSelect(
            'product_enabled',
            $this->attributesQueryBuilder->getQueryForAttribute(
                'status',
                'l.store_id',
                $productIdExpression
            )
        );

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
            );

        /* We do not include grouped and bundle products in the selection */
        $query->andWhere("IFNULL(lpvo.product_type, 'simple') != ?", 'grouped');
        $query->andWhere("IFNULL(lpvo.product_type, 'simple') != ?", 'bundle');

        /* We do not include products marked duplicate in the sample */
        $query->andWhere("JSON_EXTRACT(lp.additional_data, '$.item_duplicate_action_required') IS NULL");

        $blockingErrorsRetryHours = $this->blockingErrorConfig->getEbayBlockingErrorRetrySeconds();
        $minRetryDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $minRetryDate->modify("-$blockingErrorsRetryHours seconds");

        /* Exclude products with a blocking error */
        $query->andWhere(
            "lp.last_blocking_error_date IS NULL OR ? >= lp.last_blocking_error_date",
            $minRetryDate->format('Y-m-d H:i:s')
        );

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
            );
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
            ->addSelect(
                'stop_when_product_disabled',
                'IF(ts.stop_mode = 1 AND ts.stop_status_disabled = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'stop_when_product_out_of_stock',
                'IF(ts.stop_mode = 1 AND ts.stop_out_off_stock = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'stop_when_qty_less_than',
                'IF(ts.stop_mode = 1 AND ts.stop_qty_calculated = 1, ts.stop_qty_calculated_value, -999999)'
            )
            ->addSelect(
                'relist_when_stopped_manually',
                'IF(ts.relist_mode = 1 AND ts.relist_filter_user_lock = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'relist_when_product_status_enabled',
                'IF(ts.relist_mode = 1 AND ts.relist_status_enabled = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'relist_when_product_is_in_stock',
                'IF(ts.relist_mode = 1 AND ts.relist_is_in_stock = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'relist_when_qty_more_or_equal',
                'IF(ts.relist_mode = 1 AND ts.relist_qty_calculated = 1, relist_qty_calculated_value, 999999)'
            )
            ->from(
                'ts',
                $this->setChannelToTableName('m2epro_%s_template_synchronization')
            );
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
            ->addSelect('product_disabled', new \Zend_Db_Expr('product.product_enabled = 2'))
            ->addSelect('store_id', 'product.store_id')
            ->addSelect('sync_template_id', 'product.sync_template_id')
            ->addSelect('selling_template_id', 'product.selling_template_id')
            ->addSelect('is_in_stock', 'stock.is_in_stock')
            ->addSelect('stock_qty', 'FLOOR(stock.qty)')
            ->addSelect('online_qty', 'product.online_qty')
            ->addSelect('is_variation', 'product.is_variation')
            ->addSelect('revise_threshold', 'sync_policy.revise_threshold')
            ->addSelect('stop_when_product_disabled', 'sync_policy.stop_when_product_disabled')
            ->addSelect('stop_when_product_out_of_stock', 'sync_policy.stop_when_product_out_of_stock')
            ->addSelect('stop_when_qty_less_than', 'sync_policy.stop_when_qty_less_than')
            ->addSelect('relist_when_stopped_manually', 'sync_policy.relist_when_stopped_manually')
            ->addSelect('relist_when_product_status_enabled', 'sync_policy.relist_when_product_status_enabled')
            ->addSelect('relist_when_product_is_in_stock', 'sync_policy.relist_when_product_is_in_stock')
            ->addSelect('relist_when_qty_more_or_equal', 'sync_policy.relist_when_qty_more_or_equal')
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
            );

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
            );

        $query
            ->andWhere('pl.object_id IS NULL')
            ->andWhere('lpsa.listing_product_id IS NULL')
            ->andWhere('lpi.listing_product_id IS NULL');

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
