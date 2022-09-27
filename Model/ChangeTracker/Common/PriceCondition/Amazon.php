<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition;

use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder;

class Amazon extends AbstractPriceCondition
{
    /**
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    protected function loadSellingPolicyData(): array
    {
        $sellingPolicyQuery = $this->queryBuilder
            ->makeSubQuery();

        $sellingPolicyQuery
            ->addSelect('id', 'template_selling_format_id')
            ->addSelect('vat', 'regular_price_vat_percent')
            ->addSelect('modifier', 'regular_price_coefficient')
            ->addSelect('mode', 'regular_price_mode')
            ->addSelect('mode_attribute', 'regular_price_custom_attribute')
        ;

        $sellingPolicyQuery->from('t', 'm2epro_amazon_template_selling_format');

        return $sellingPolicyQuery->fetchAll();
    }

    /**
     * @ingeritdoc
     */
    protected function buildThenCondition(string $modifiers, float $vat, string $priceColumn): string
    {
        $pattern = '/(?<sing>[+\-])?(?<number>[0-9\.]+)(?<percent>\%)?/';
        $matchesCount = preg_match($pattern, $modifiers, $m);
        if ($matchesCount === false || $matchesCount === 0) {
            return "ROUND( $priceColumn * (1+$vat/100), 2)";
        }

        $sign = $m['sign'] ?? '';
        $number = $m['number'] ?? '';
        $percent = $m['percent'] ?? '';

        if ($sign === '' && $percent === '') {
            return "ROUND( ($priceColumn * $number) * (1 + $vat/100), 2)";
        }

        $sign = $sign === '' ? '+' : $sign;
        if ($percent === '') {
            return "ROUND( ($priceColumn $sign $number) * (1 + $vat/100), 2)";
        }

        return "ROUND( ($priceColumn * (1 $sign $number/100)) * (1 + $vat/100), 2)";
    }
}
