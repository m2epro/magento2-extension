<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Base;

abstract class AbstractPriceTracker implements \Ess\M2ePro\Model\ChangeTracker\TrackerInterface
{
    use \Ess\M2ePro\Model\ChangeTracker\Traits\TableNameReplacerTrait;
    use \Ess\M2ePro\Model\ChangeTracker\Traits\AffectedMagentoProductLoaderTrait;

    private \Ess\M2ePro\Model\ChangeTracker\TrackerConfiguration $configuration;
    private \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory $queryBuilderFactory;
    private \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder $attributesQueryBuilder;
    private \Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition\AbstractPriceCondition $priceConditionBuilder;
    private \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger;

    public function __construct(
        \Ess\M2ePro\Model\ChangeTracker\TrackerConfiguration $configuration,
        \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory $queryBuilderFactory,
        \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder $attributesQueryBuilder,
        \Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition\PriceConditionFactory $conditionFactory,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger
    ) {
        $this->configuration = $configuration;
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->attributesQueryBuilder = $attributesQueryBuilder;
        $this->priceConditionBuilder = $conditionFactory->create($configuration->channel);
        $this->logger = $logger;
    }

    public function getType(): string
    {
        return \Ess\M2ePro\Model\ChangeTracker\TrackerInterface::TYPE_PRICE;
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
     * Condition for select, in which we calculate the online price of the product.
     * @return string
     */
    abstract protected function getOnlinePriceCondition(): string;

    /**
     * The field in which the currency for the marketplace. `m2epro_<chanel>_marketplace` tables.
     * For Amazon and Walmart this is `default_currency`, for eBay it is `currency`
     * @return string
     */
    abstract protected function getMarketplaceCurrencyField(): string;

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

        $mainQuery->andWhere('calculated_price IS NOT NULL')
            // The condition `calculated_price != online_price` is not suitable,
            // since rounding may not work correctly https://stackoverflow.com/a/41484519
                  ->andWhere('ABS(calculated_price - online_price) > 0.01')
                  ->andWhere('base.status = ?', \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED)
                  ->andWhere('base.revise_update_price = 1');

        return $mainQuery->getQuery();
    }

    public function processQueryRow(array $row): ?array
    {
        return [
            'listing_product_id' => $row['listing_product_id'],
            'type' => \Ess\M2ePro\Model\ChangeTracker\ChangeHolder::INSTRUCTION_TYPE_CHANGE_TRACKER_PRICE,
            'component' => $this->getChannel(),
            'initiator' => sprintf('%s_%s', $this->getType(), $this->getChannel()),
            'additional_data' => $row['additional_data'] ?? null,
            'priority' => 100,
            'create_date' => new \Zend_Db_Expr('NOW()'),
        ];
    }

    /**
     * @throws \Zend_Db_Statement_Exception
     * @throws \Exception
     */
    protected function productSubQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
    {
        $query = $this->queryBuilderFactory->createSelect();
        $query->distinct();

        $query->addSelect('listing_product_id', 'lp.id');

        $productIdExpression = 'IFNULL(lpvo.product_id, lp.product_id)';
        $query->addSelect('product_id', $productIdExpression);

        $query
            ->addSelect('status', 'lp.status')
            ->addSelect('store_id', 'l.store_id')
            ->addSelect('sync_template_id', 'c_l.template_synchronization_id')
            ->addSelect('selling_template_id', 'c_l.template_selling_format_id');

        $query->addSelect('online_price', $this->getOnlinePriceCondition());
        $query->addSelect('currency_rate', (string)$this->getCurrencyRateSubQuery());

        /* Select base attributes */
        $attributes = [
            ['name' => 'price', 'alias' => 'price',],
            ['name' => 'special_price', 'alias' => 'special_price',],
            ['name' => 'special_from_date', 'alias' => 'special_from_date',],
            ['name' => 'special_to_date', 'alias' => 'special_to_date',],
        ];

        foreach ($attributes as $attribute) {
            $alias = $attribute['alias'];
            $attributeQuery = $this->attributesQueryBuilder->getQueryForAttribute(
                $attribute['name'],
                'l.store_id',
                $productIdExpression
            );

            $query->addSelect($alias, (string)$attributeQuery);
        }

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
            ->leftJoin(
                'marketplace',
                $this->setChannelToTableName('m2epro_%s_marketplace'),
                'marketplace.marketplace_id = l.marketplace_id'
            )->andWhere(
                sprintf(
                    'lp.id >= %s AND lp.id <= %s',
                    $this->getListingProductIdFrom(),
                    $this->getListingProductIdTo()
                )
            );

        /* We do not include grouped and bundle products */
        $query->andWhere("IFNULL(lpvo.product_type, 'simple') != ?", 'grouped');
        $query->andWhere("IFNULL(lpvo.product_type, 'simple') != ?", 'bundle');

        /* We do not include products marked duplicate */
        $query->andWhere("JSON_EXTRACT(lp.additional_data, '$.item_duplicate_action_required') IS NULL");

        $dateTimeModifier = sprintf('-%s seconds', \Ess\M2ePro\Model\Tag\BlockingErrors::RETRY_ACTION_SECONDS);
        $minRetryDate = \Ess\M2ePro\Helper\Date::createCurrentGmt()->modify($dateTimeModifier);

        /* Exclude products with a blocking error */
        $query->andWhere(
            "lp.last_blocking_error_date IS NULL OR ? >= lp.last_blocking_error_date",
            $minRetryDate->format('Y-m-d H:i:s')
        );

        $query->addGroup('lp.id');
        $query->addGroup('IFNULL(lpvo.product_id, lp.product_id)');

        return $query;
    }

    protected function synchronizationPolicySubQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
    {
        return $this->queryBuilderFactory
            ->createSelect()
            ->addSelect('template_synchronization_id', 'ts.template_synchronization_id')
            ->addSelect('revise_update_price', 'ts.revise_update_price')
            ->from(
                'ts',
                $this->setChannelToTableName('m2epro_%s_template_synchronization')
            );
    }

    /**
     * @inheridoc
     * @throws \Zend_Db_Statement_Exception
     */
    protected function getSelectQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
    {
        $query = $this->queryBuilderFactory->createSelect();

        /* Required selects */
        $query->addSelect('listing_product_id', 'product.listing_product_id');
        $query->addSelect('calculated_price', $this->priceConditionBuilder->getCondition());

        $query->addSelect('product_id', 'product.product_id')
              ->addSelect('status', 'product.status')
              ->addSelect('store_id', 'product.store_id')
              ->addSelect('sync_template_id', 'product.sync_template_id')
              ->addSelect('selling_template_id', 'product.selling_template_id')
              ->addSelect('online_price', 'product.online_price')
              ->addSelect('revise_update_price', 'sync_policy.revise_update_price');

        /* Tables */
        $query
            ->from('product', $this->productSubQuery())
            ->leftJoin(
                'sync_policy',
                $this->synchronizationPolicySubQuery(),
                'sync_policy.template_synchronization_id = product.sync_template_id'
            );

        /* Groups */
        $query
            ->addGroup('product.listing_product_id')
            ->addGroup('product.product_id');

        return $query;
    }

    /**
     * @return \Zend_Db_Expr
     * @throws \Zend_Db_Statement_Exception
     * @throws \Exception
     */
    protected function getCurrencyRateSubQuery(): \Zend_Db_Expr
    {
        $select = $this->queryBuilderFactory->createSelect();
        $select->distinct();
        $select->addSelect('store', 'listing.store_id');
        $select->addSelect('marketplace', 'marketplace.marketplace_id');
        $select->addSelect('rate', 'rate.rate');
        $select->from('listing', 'm2epro_listing');
        $select->innerJoin(
            'marketplace',
            $this->setChannelToTableName('m2epro_%s_marketplace'),
            'listing.marketplace_id = marketplace.marketplace_id'
        );
        $select->leftJoin(
            'store',
            'store',
            'store.store_id = listing.store_id'
        );
        $select->leftJoin(
            'website_currency',
            'core_config_data',
            sprintf(
                "website_currency.scope_id = store.website_id
                AND website_currency.scope = '%s'
                AND website_currency.path = '%s'",
                'websites',
                \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE
            )
        );
        $select->leftJoin(
            'default_currency',
            'core_config_data',
            sprintf(
                "default_currency.scope_id = %s
                AND default_currency.scope = '%s'
                AND default_currency.path = '%s'",
                \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                'default',
                \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE
            )
        );
        $select->innerJoin(
            'rate',
            'directory_currency_rate',
            sprintf(
                'rate.currency_from = IFNULL(website_currency.value, default_currency.value)
                AND rate.currency_to = marketplace.%s',
                $this->getMarketplaceCurrencyField()
            )
        );
        $select->andWhere('listing.component_mode = ?', $this->getChannel());

        $select->leftJoin(
            'listing_product',
            'm2epro_listing_product',
            'listing.id = listing_product.listing_id'
        )->andWhere(
            sprintf(
                'listing_product.id >= %s AND listing_product.id <= %s',
                $this->getListingProductIdFrom(),
                $this->getListingProductIdTo()
            )
        );

        $ratesByStores = $select->fetchAll();

        $this->logger->debug('Get Currency Rates', [
            'sql' => (string)$select,
            'result' => $ratesByStores,
            'tracker' => $this,
        ]);

        if ($ratesByStores === []) {
            return new \Zend_Db_Expr('1');
        }

        $sql = 'CASE';
        foreach ($ratesByStores as $data) {
            $sql .= sprintf(
                ' WHEN l.store_id = %s AND l.marketplace_id = %s THEN %s',
                $data['store'],
                $data['marketplace'],
                $data['rate']
            );
        }
        $sql .= ' END';

        return new \Zend_Db_Expr($sql);
    }
}
