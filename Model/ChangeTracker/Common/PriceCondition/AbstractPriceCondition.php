<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition;

use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder;
use Ess\M2ePro\Model\Listing\Product\PriceRounder;

abstract class AbstractPriceCondition
{
    /** @var array */
    protected $sellingPolicyData;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder */
    protected $attributesQueryBuilder;
    /** @var \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder */
    protected $queryBuilder;
    /** @var string */
    protected $channel;

    /**
     * @param string $channel
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder $attributesQueryBuilder
     * @param \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder $queryBuilder
     */
    public function __construct(
        string $channel,
        ProductAttributesQueryBuilder $attributesQueryBuilder,
        \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder $queryBuilder
    ) {
        $this->channel = $channel;
        $this->attributesQueryBuilder = $attributesQueryBuilder;
        $this->queryBuilder = $queryBuilder;

        $this->sellingPolicyData = $this->loadSellingPolicyData();
    }

    /**
     * @return array
     */
    abstract protected function loadSellingPolicyData(): array;

    /**
     * @return string
     */
    public function getCondition(): string
    {
        $queryData = [];
        foreach ($this->sellingPolicyData as $sellingPolicy) {
            $priceColumn = $this->getPriceColumnCondition(
                (int)$sellingPolicy['mode'],
                $sellingPolicy['mode_attribute']
            );

            $queryData[] = [
                'when' => (int)$sellingPolicy['id'],
                'then' => $this->buildThenCondition(
                    $priceColumn,
                    $sellingPolicy['modifier'],
                    (float)$sellingPolicy['vat'],
                    (int)($sellingPolicy['price_rounding'] ?? PriceRounder::PRICE_ROUNDING_NONE)
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

        if ($caseBody === '') {
            return new \Zend_Db_Expr(0);
        }

        return "CASE $caseBody END";
    }

    /**
     * @param int $mode
     * @param string $modeAttribute
     *
     * @return string
     * @throws \Exception
     */
    protected function getPriceColumnCondition(int $mode, string $modeAttribute): string
    {
        if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT) {
            return 'product.price * product.currency_rate';
        }

        if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL) {
            $now = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
            $nowDate = (clone $now)->format('Y-m-d H:i:s');
            $startYear = (clone $now)->format('Y-01-01 00:00:00');
            $endYear = (clone $now)->modify('+1 year')->format('Y-01-01 00:00:00');

            return "
                ( IF((product.special_price IS NOT NULL
                    AND '$nowDate' >= IFNULL(product.special_from_date, '$startYear')
                    AND '$nowDate' < IFNULL(product.special_to_date, '$endYear')
                  ), product.special_price, product.price) * product.currency_rate
                )
            ";
        }

        if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE) {
            $attributeQuery = $this->attributesQueryBuilder
                ->getQueryForAttribute(
                    $modeAttribute,
                    'product.store_id',
                    'product.product_id'
                );

            return "($attributeQuery) * product.currency_rate";
        }

        throw new \RuntimeException(
            sprintf(
                'Wrong selling policy mode %s for channel %s',
                $mode,
                $this->channel
            )
        );
    }

    protected function buildThenCondition(
        string $priceColumn,
        string $modifiers,
        float $vat,
        int $priceRounding
    ): string {
        $modifiers = json_decode($modifiers, true);
        $sql = $priceColumn;
        foreach ($modifiers as $modifier) {
            $mode = (int)$modifier['mode'];
            $value = $modifier['value'] ?? '';
            $attributeCode = $modifier['attribute_code'] ?? '';

            if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODIFIER_ABSOLUTE_INCREASE) {
                $sql = "( $sql + $value )";
                continue;
            }

            if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODIFIER_ABSOLUTE_DECREASE) {
                $sql = "( $sql - $value )";
                continue;
            }

            if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODIFIER_PERCENTAGE_INCREASE) {
                $sql = "( $sql * (1+$value/100) )";
                continue;
            }

            if ($mode ===  \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODIFIER_PERCENTAGE_DECREASE) {
                $sql = "( $sql * (1-$value/100) )";
                continue;
            }

            $attrQuery = $this->attributesQueryBuilder
                ->getQueryForAttribute(
                    $attributeCode,
                    'product.store_id',
                    'product.product_id'
                );

            if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODIFIER_ATTRIBUTE_INCREASE) {
                $sql = "( $sql + ({$attrQuery}) )";
                continue;
            }

            if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODIFIER_ATTRIBUTE_DECREASE) {
                $sql = "( $sql - ({$attrQuery}) )";
                continue;
            }

            if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODIFIER_ATTRIBUTE_PERCENTAGE_INCREASE) {
                $sql = "$sql + ($sql * ($attrQuery) / 100)";
                continue;
            }

            if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODIFIER_ATTRIBUTE_PERCENTAGE_DECREASE) {
                $sql = "$sql - ($sql * ($attrQuery) / 100)";
            }
        }

        if ($priceRounding === PriceRounder::PRICE_ROUNDING_NEAREST_HUNDREDTH) {
            return "ROUND( $sql * (1+$vat/100), 1 ) - 0.01";
        }

        if ($priceRounding === PriceRounder::PRICE_ROUNDING_NEAREST_TENTH) {
            return "ROUND( $sql * (1+$vat/100) ) - 0.01";
        }

        if ($priceRounding === PriceRounder::PRICE_ROUNDING_NEAREST_INT) {
            return "ROUND( $sql * (1+$vat/100) )";
        }

        if ($priceRounding === PriceRounder::PRICE_ROUNDING_NEAREST_HUNDRED) {
            return "ROUND( $sql * (1+$vat/100), -1 )";
        }

        return "ROUND( $sql * (1+$vat/100), 2 )";
    }
}
