<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Base;

abstract class AbstractInventoryTracker implements \Ess\M2ePro\Model\ChangeTracker\TrackerInterface
{
    use \Ess\M2ePro\Model\ChangeTracker\Traits\TableNameReplacerTrait;
    use \Ess\M2ePro\Model\ChangeTracker\Traits\AffectedMagentoProductLoaderTrait;

    private \Ess\M2ePro\Model\ChangeTracker\TrackerConfiguration $configuration;
    private \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\InventoryStock $inventoryStock;
    private \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder $attributesQueryBuilder;
    private \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger;
    private \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\EnterpriseChecker $enterpriseChecker;
    private \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory $queryBuilderFactory;

    public function __construct(
        \Ess\M2ePro\Model\ChangeTracker\TrackerConfiguration $configuration,
        \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory $queryBuilderFactory,
        \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\InventoryStock $inventoryStock,
        \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder $attributesQueryBuilder,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\EnterpriseChecker $enterpriseChecker
    ) {
        $this->configuration = $configuration;
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->inventoryStock = $inventoryStock;
        $this->attributesQueryBuilder = $attributesQueryBuilder;
        $this->logger = $logger;
        $this->enterpriseChecker = $enterpriseChecker;
    }

    public function getType(): string
    {
        return \Ess\M2ePro\Model\ChangeTracker\TrackerInterface::TYPE_INVENTORY;
    }

    public function getChannel(): string
    {
        return $this->configuration->channel;
    }

    public function getListingProductIdFrom(): int
    {
        return $this->configuration->listingProductIdFrom;
    }

    public function getListingProductIdTo(): int
    {
        return $this->configuration->listingProductIdTo;
    }

    /**
     * @return array<int>
     * @throws \Zend_Db_Statement_Exception
     */
    public function getAffectedMagentoProductIds(): array
    {
        return $this->loadAffectedMagentoProductIds();
    }

    /**
     * @return \Magento\Framework\DB\Select
     * @throws \Exception
     */
    public function getDataQuery(): \Magento\Framework\DB\Select
    {
        $query = $this->getSelectQuery();

        $mainQuery = $this->queryBuilderFactory
            ->createSelect()
            ->distinct()
            ->addSelect('listing_product_id', 'base.listing_product_id')
            ->from('base', $query);

        /**
         * List condition
         * @see \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker\NotListed::isMeetListRequirements
         * @see \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker\NotListed::isMeetListRequirements
         * @see \Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker\NotListed::isMeetListRequirements
         */
        $notListedIsMeetList = '
            status = 0 AND (
                IF (list_only_enabled_products AND base.product_disabled = 1, FALSE,
                    IF (base.list_only_in_stock_products AND base.is_in_stock = 0, FALSE,
                        base.calculated_qty >= base.list_with_qty_greater_or_equal_then
                    )
                )
            )
        ';
        $mainQuery->orWhere($notListedIsMeetList);

        /**
         * Revise condition
         * @see \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker\Active::isMeetReviseQtyRequirements
         * @see \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker\Active::isMeetReviseQtyRequirements
         * @see \Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker\Active::isMeetReviseQtyRequirements
         */
        $activeIsMeetRevise = '
            base.status = 2 AND (
                (base.calculated_qty > base.online_qty AND base.online_qty < base.revise_threshold)
                OR (base.calculated_qty != base.online_qty AND base.calculated_qty < base.revise_threshold)
            )
        ';
        $mainQuery->orWhere($activeIsMeetRevise);

        /**
         * Relist condition
         * @see \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker\Inactive::isMeetRelistRequirements
         * @see \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker\Inactive::isMeetRelistRequirements
         * @see \Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker\Inactive::isMeetRelistRequirements
         */
        $inactiveIsMeetRelist = '
            base.status IN (1, 3, 4, 5) AND (
                IF(base.relist_when_stopped_manually AND base.status_changer <> 2, FALSE,
                   IF(base.relist_when_product_is_in_stock AND base.is_in_stock = 0, FALSE,
                      IF(base.relist_when_product_status_enabled AND base.product_disabled = 1, FALSE,
                         base.calculated_qty >= base.relist_when_qty_more_or_equal
                      )
                   )
                )
            )
        ';
        $mainQuery->orWhere($inactiveIsMeetRelist);

        /**
         * Stop condition
         * @see \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker\Active::isMeetStopRequirements
         * @see \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker\Active::isMeetStopRequirements
         * @see \Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker\Active::isMeetStopRequirements
         */
        $activeIsMeetStop = '
            base.status = 2 AND (
                IF (base.stop_when_product_disabled AND base.product_disabled = 1, TRUE,
                    IF (base.stop_when_product_out_of_stock AND base.is_in_stock = 0, TRUE,
                        IF (base.stop_when_qty_less_than >= base.calculated_qty, TRUE, FALSE)
                    )
                )
            )
        ';
        $mainQuery->orWhere($activeIsMeetStop);

        return $mainQuery->getQuery();
    }

    public function processQueryRow(array $row): ?array
    {
        return [
            'listing_product_id' => $row['listing_product_id'],
            'type' => \Ess\M2ePro\Model\ChangeTracker\ChangeHolder::INSTRUCTION_TYPE_CHANGE_TRACKER_QTY,
            'component' => $this->getChannel(),
            'initiator' => sprintf('%s_%s', $this->getType(), $this->getChannel()),
            'additional_data' => $row['additional_data'] ?? null,
            'priority' => 100,
            'create_date' => new \Zend_Db_Expr('NOW()'),
        ];
    }

    /**
     * Base product sub query.
     * Includes all necessary information regarding the listing product
     * @throws \Exception
     */
    protected function productSubQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
    {
        $query = $this->queryBuilderFactory->createSelect();
        $query->distinct();

        $listingProductIdExpression = 'lp.id';
        $query->addSelect('listing_product_id', $listingProductIdExpression);

        $productIdExpression = 'IFNULL(lpvo.product_id, lp.product_id)';
        $query->addSelect('product_id', $productIdExpression);

        $query
            ->addSelect('status', 'lp.status')
            ->addSelect('status_changer', 'lp.status_changer')
            ->addSelect('store_id', 'l.store_id')
            ->addSelect('sync_template_id', 'c_l.template_synchronization_id')
            ->addSelect('selling_template_id', 'c_l.template_selling_format_id')
            ->addSelect('is_variation', 'IFNULL(c_lp.is_variation_parent, 0)');

        $query->addSelect(
            'product_enabled',
            (string)$this->attributesQueryBuilder->getQueryForAttribute(
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
            )
            ->andWhere(
                sprintf(
                    'lp.id >= %s AND lp.id <= %s',
                    $this->getListingProductIdFrom(),
                    $this->getListingProductIdTo()
                )
            );

        /* We do not include grouped and bundle products in the selection */
        $query->andWhere("IFNULL(lpvo.product_type, 'simple') != ?", 'grouped');
        $query->andWhere("IFNULL(lpvo.product_type, 'simple') != ?", 'bundle');

        /* We do not include products marked duplicate in the sample */
        $query->andWhere("JSON_EXTRACT(lp.additional_data, '$.item_duplicate_action_required') IS NULL");

        $dateTimeModifier = sprintf('-%s seconds', \Ess\M2ePro\Model\Tag\BlockingErrors::RETRY_ACTION_SECONDS);
        $minRetryDate = \Ess\M2ePro\Helper\Date::createCurrentGmt()->modify($dateTimeModifier);

        /* Exclude products with a blocking error */
        $query->andWhere(
            "lp.last_blocking_error_date IS NULL OR ? >= lp.last_blocking_error_date",
            $minRetryDate->format('Y-m-d H:i:s')
        );

        return $query;
    }

    protected function sellingPolicySubQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
    {
        return $this->queryBuilderFactory
            ->createSelect()
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

    protected function synchronizationPolicySubQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
    {
        return $this->queryBuilderFactory
            ->createSelect()
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
                'list_only_enabled_products',
                'IF(ts.list_mode = 1 AND ts.list_status_enabled = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'list_only_in_stock_products',
                'IF(ts.list_mode = 1 AND ts.list_is_in_stock = 1, TRUE, FALSE)'
            )
            ->addSelect(
                'list_with_qty_greater_or_equal_then',
                'IF(ts.list_mode = 1 AND ts.list_qty_calculated = 1, ts.list_qty_calculated_value, 999999)'
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
                'IF(ts.relist_mode = 1 AND ts.relist_filter_user_lock = 0, TRUE, FALSE)'
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
     * @throws \Exception
     */
    protected function getSelectQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
    {
        $query = $this->queryBuilderFactory->createSelect();

        /* Selects */
        $query
            ->addSelect('listing_product_id', 'product.listing_product_id')
            ->addSelect('product_id', 'product.product_id')
            ->addSelect('status', 'product.status')
            ->addSelect('status_changer', 'product.status_changer')
            ->addSelect(
                'product_disabled',
                (string)(new \Zend_Db_Expr('product.product_enabled = 2'))
            )
            ->addSelect('store_id', 'product.store_id')
            ->addSelect('sync_template_id', 'product.sync_template_id')
            ->addSelect('selling_template_id', 'product.selling_template_id')
            ->addSelect('is_in_stock', 'stock.is_in_stock')
            ->addSelect('stock_qty', 'FLOOR(stock.qty)')
            ->addSelect('is_variation', 'product.is_variation')
            ->addSelect('revise_threshold', 'sync_policy.revise_threshold')
            ->addSelect('list_with_qty_greater_or_equal_then', 'sync_policy.list_with_qty_greater_or_equal_then')
            ->addSelect('list_only_enabled_products', 'sync_policy.list_only_enabled_products')
            ->addSelect('list_only_in_stock_products', 'sync_policy.list_only_in_stock_products')
            ->addSelect('stop_when_product_disabled', 'sync_policy.stop_when_product_disabled')
            ->addSelect('stop_when_product_out_of_stock', 'sync_policy.stop_when_product_out_of_stock')
            ->addSelect('stop_when_qty_less_than', 'sync_policy.stop_when_qty_less_than')
            ->addSelect('relist_when_stopped_manually', 'sync_policy.relist_when_stopped_manually')
            ->addSelect('relist_when_product_status_enabled', 'sync_policy.relist_when_product_status_enabled')
            ->addSelect('relist_when_product_is_in_stock', 'sync_policy.relist_when_product_is_in_stock')
            ->addSelect('relist_when_qty_more_or_equal', 'sync_policy.relist_when_qty_more_or_equal');

        $query->addSelect('online_qty', 'product.online_qty');
        $query->addSelect('calculated_qty', $this->calculatedQtyExpression());

        /* Tables */
        $query
            ->from('product', $this->productSubQuery())
            ->innerJoin(
                'stock',
                $this->inventoryStock->getInventoryStockQuery($this),
                $this->inventoryStock->getInventoryStockJoinCondition()
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

        $entityVarcharCondition = 'cpev.attribute_id = ea.attribute_id';
        $entityVarcharCondition .= ' AND cpev.store_id = product.store_id';
        $entityVarcharCondition .= sprintf(
            ' AND cpev.%s = product.product_id',
            $this->enterpriseChecker->isEnterpriseEdition() ? 'row_id' : 'entity_id'
        );

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

        return $query;
    }

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
