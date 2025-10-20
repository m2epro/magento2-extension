<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition;

class Walmart extends AbstractPriceCondition
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
            ->addSelect('vat', 'price_vat_percent')
            ->addSelect('modifier', 'price_modifier')
            ->addSelect('mode', 'price_mode')
            ->addSelect('mode_attribute', 'price_custom_attribute')
            ->addSelect('price_rounding', 'price_rounding_option');

        $sellingPolicyQuery->from('t', 'm2epro_walmart_template_selling_format');

        return $sellingPolicyQuery->fetchAll();
    }
}
