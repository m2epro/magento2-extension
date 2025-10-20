<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition;

class Amazon extends AbstractPriceCondition
{
    private \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory $queryBuilderFactory;

    public function __construct(
        \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\QueryBuilderFactory $queryBuilderFactory,
        string $channel,
        \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder $attributesQueryBuilder,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\MagentoAttributes $magentoAttributes,
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger
    ) {
        parent::__construct(
            $channel,
            $attributesQueryBuilder,
            $magentoAttributes,
            $moduleConfiguration,
            $logger
        );
        $this->queryBuilderFactory = $queryBuilderFactory;
    }

    /**
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    protected function loadSellingPolicyData(): array
    {
        $sellingPolicyQuery = $this->queryBuilderFactory->createSelect();

        $sellingPolicyQuery
            ->addSelect('id', 'template_selling_format_id')
            ->addSelect('vat', 'regular_price_vat_percent')
            ->addSelect('modifier', 'regular_price_modifier')
            ->addSelect('mode', 'regular_price_mode')
            ->addSelect('mode_attribute', 'regular_price_custom_attribute')
            ->addSelect('price_rounding', 'price_rounding_option');

        $sellingPolicyQuery->from('t', 'm2epro_amazon_template_selling_format');

        return $sellingPolicyQuery->fetchAll();
    }
}
