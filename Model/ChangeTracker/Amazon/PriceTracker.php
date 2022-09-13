<?php

namespace Ess\M2ePro\Model\ChangeTracker\Amazon;

use Ess\M2ePro\Model\ChangeTracker\Base\BasePriceTracker;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder;

class PriceTracker extends BasePriceTracker
{
    /**
     * @inheridoc
     * @throws \Zend_Db_Statement_Exception
     */
    protected function getSelectQuery(): SelectQueryBuilder
    {
        $query = parent::getSelectQuery();

        $query->addSelect('calculated_price', $this->makeCalculatedPriceCondition());

        return $query;
    }

    /**
     * @return string
     * @throws \Zend_Db_Statement_Exception
     */
    protected function makeCalculatedPriceCondition(): string
    {
        $sellingPolicyQuery = $this->queryBuilder->makeSubQuery();
        $sellingPolicyQuery
            ->addSelect('id', 'template_selling_format_id')
            ->addSelect('mode', 'regular_price_mode')
            ->addSelect('modifier', 'regular_price_coefficient')
            ->addSelect('vat', 'regular_price_vat_percent')
            ->addSelect('mode_attribute', 'regular_price_custom_attribute')
            ->from(
                't',
                $this->setChannelToTableName('m2epro_%s_template_selling_format')
            )
        ;

        $queryData = [];
        foreach ($sellingPolicyQuery->fetchAll() as $sellingPolicy) {
            $queryData[] = [
                'when' => (int)$sellingPolicy['id'],
                'then' => $this->buildFormula(
                    $sellingPolicy['modifier'],
                    $sellingPolicy['vat'],
                    $this->getPriceColumn((int)$sellingPolicy['mode'], $sellingPolicy['mode_attribute'])
                ),
            ];
        }

        $caseBody = '';
        foreach ($queryData as $qd) {
            $caseBody .= "
                WHEN product.selling_template_id = {$qd['when']}
                    THEN {$qd['then']}
            ";
        }

        return "CASE $caseBody END";
    }

    /**
     * @param string $modifier
     * @param float $vat
     *
     * @return string
     */
    protected function buildFormula(string $modifier, float $vat, string $priceColumn): string
    {
        $pattern = '/(?<sing>[+\-])?(?<number>[0-9\.]+)(?<percent>\%)?/';
        $matchesCount = preg_match($pattern, $modifier, $m);
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
