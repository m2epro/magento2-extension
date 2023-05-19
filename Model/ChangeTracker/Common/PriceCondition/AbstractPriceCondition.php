<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition;

use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder;

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
                    $sellingPolicy['modifier'],
                    $sellingPolicy['vat'],
                    $priceColumn
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
     */
    protected function getPriceColumnCondition(int $mode, string $modeAttribute): string
    {
        if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT) {
            return 'product.price';
        }

        if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_SPECIAL) {
            return "
                (CASE
                    WHEN product.special_price IS NOT NULL
                        AND product.special_from_date IS NOT NULL
                        AND product.special_to_date IS NOT NULL
                        AND NOW() BETWEEN product.special_from_date AND product.special_to_date
                    THEN product.special_price
                    WHEN product.special_price IS NOT NULL
                        AND product.special_from_date IS NOT NULL
                        AND product.special_from_date + INTERVAL 1 YEAR > NOW()
                    THEN product.special_price
                    ELSE product.price
                END)
            ";
        }

        if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_ATTRIBUTE) {
            $attributeQuery = $this->attributesQueryBuilder
                ->getQueryForAttribute(
                    $modeAttribute,
                    'product.store_id',
                    'product.product_id'
                );

            return "($attributeQuery)";
        }

        throw new \RuntimeException(
            sprintf(
                'Wrong selling policy mode %s for channel %s',
                $mode,
                $this->channel
            )
        );
    }

    /**
     * @param string $modifiers
     * @param float $vat
     * @param string $priceColumn
     *
     * @return string
     */
    protected function buildThenCondition(string $modifiers, float $vat, string $priceColumn): string
    {
        $modifiers = json_decode($modifiers, true);
        $sql = $priceColumn;
        foreach ($modifiers as $modifier) {
            $mode = (int)$modifier['mode'];
            $value = $modifier['value'] ?? '';
            $attributeCode = $modifier['attribute_code'] ?? '';

            // TODO use constants
            switch ($mode) {
                case 1:
                    $sql = "( $sql + $value )";
                    break;
                case 2:
                    $sql = "( $sql - $value )";
                    break;
                case 3:
                    $sql = "( $sql * (1+$value/100) )";
                    break;
                case 4:
                    $sql = "( $sql * (1-$value/100) )";
                    break;
                case 5:
                    $attrQuery = $this->attributesQueryBuilder
                        ->getQueryForAttribute(
                            $attributeCode,
                            'product.store_id',
                            'product.product_id'
                        );
                    $sql = "( $sql + ({$attrQuery}) )";
                    break;
            }
        }

        return "ROUND( $sql * (1+$vat/100) * product.currency_rate, 2)";
    }
}
