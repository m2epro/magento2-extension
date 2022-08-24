<?php

namespace Ess\M2ePro\Model\ChangeTracker\Base;

use Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder;

abstract class BasePriceTracker implements TrackerInterface
{
    /** @var string */
    private $channel;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder */
    protected $queryBuilder;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger */
    protected $logger;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder */
    protected $attributesQueryBuilder;

    /**
     * @param string $channel
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory $queryBuilderFactory
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder $attributesQueryBuilder
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger
     */
    public function __construct(
        string $channel,
        QueryBuilderFactory $queryBuilderFactory,
        ProductAttributesQueryBuilder $attributesQueryBuilder,
        TrackerLogger $logger
    ) {
        $this->channel = $channel;
        $this->queryBuilder = $queryBuilderFactory->make();
        $this->attributesQueryBuilder = $attributesQueryBuilder;
        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return TrackerInterface::TYPE_PRICE;
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
            ->andWhere('calculated_price != online_price');

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
     * @return \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
     */
    protected function productSubQuery(): SelectQueryBuilder
    {
        $query = $this->queryBuilder->makeSubQuery();
        $query->distinct();

        $query->addSelect('listing_product_id', 'lp.id');

        $productIdExpression = 'IFNULL(IFNULL(lpvo.product_id, lp.product_id), 0)';
        $query->addSelect('product_id', $productIdExpression);

        $query
            ->addSelect('status', 'lp.status')
            ->addSelect('store_id', 'l.store_id')
            ->addSelect('sync_template_id', 'c_l.template_synchronization_id')
            ->addSelect('selling_template_id', 'c_l.template_selling_format_id')
        ;

        $onlinePriceExpression = 'COALESCE(c_lp.online_regular_price, 0)';
        $query->addSelect('online_price', $onlinePriceExpression);

        $attributes = [
            ['name' => 'price', 'alias' => 'price', 'aggregate_function' => 'SUM'],
            ['name' => 'special_price', 'alias' => 'special_price', 'aggregate_function' => 'SUM'],
            ['name' => 'special_from_date', 'alias' => 'special_from_date', 'aggregate_function' => 'MAX'],
            ['name' => 'special_to_date', 'alias' => 'special_to_date', 'aggregate_function' => 'MAX'],
        ];

        foreach ($attributes as $attribute) {
            $aggFunc = $attribute['aggregate_function'];
            $alias = $attribute['alias'];
            $attributeQuery = $this->attributesQueryBuilder->getQueryForAttribute(
                $attribute['name'],
                $productIdExpression
            );

            $subQuery = "IF(" .
                "lpvo.product_type = 'bundle', $aggFunc( ($attributeQuery) ), ($attributeQuery))";

            $query->addSelect($alias, $subQuery);
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
        ;

        $query->addGroup('lp.id');
        $query->addGroup('COALESCE(lpvo.product_id, lp.product_id)');

        return $query;
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
            ->addSelect('online_price', 'product.online_price')
        ;

        /* Tables */
        $query
            ->from('product', $this->productSubQuery())
            ->leftJoin(
                'sync_policy',
                $this->synchronizationPolicySubQuery(),
                'sync_policy.template_synchronization_id = product.sync_template_id'
            )
            ->leftJoin(
                'cped',
                'catalog_product_entity_decimal',
                'cped.entity_id = product.product_id'
            )
            ->innerJoin(
                'ea',
                'eav_attribute',
                'ea.attribute_id = cped.attribute_id AND ea.attribute_code = \'price\''
            )
        ;

        $query
            ->addGroup('product.listing_product_id')
            ->addGroup('product.product_id')
        ;

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
}
