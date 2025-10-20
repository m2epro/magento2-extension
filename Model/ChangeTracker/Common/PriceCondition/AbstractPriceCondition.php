<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition;

use Ess\M2ePro\Model\Listing\Product\PriceRounder;

abstract class AbstractPriceCondition
{
    private string $channel;
    private \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder $attributesQueryBuilder;
    private \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\MagentoAttributes $magentoAttributes;
    private \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration;
    private \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger;

    public function __construct(
        string $channel,
        \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder $attributesQueryBuilder,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\MagentoAttributes $magentoAttributes,
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\TrackerLogger $logger
    ) {
        $this->channel = $channel;
        $this->attributesQueryBuilder = $attributesQueryBuilder;
        $this->magentoAttributes = $magentoAttributes;
        $this->moduleConfiguration = $moduleConfiguration;
        $this->logger = $logger;
    }

    abstract protected function loadSellingPolicyData(): array;

    /**
     * @throws \Exception
     */
    public function getCondition(): string
    {
        $sellingPolicyData = $this->loadSellingPolicyData();

        $queryData = [];
        foreach ($sellingPolicyData as $sellingPolicy) {
            try {
                $priceColumn = $this->getPriceColumnCondition(
                    (int)$sellingPolicy['mode'],
                    $sellingPolicy['mode_attribute']
                );

                $modifiers = [];
                if (!empty($sellingPolicy['modifier'])) {
                    $decodedModifiers = json_decode($sellingPolicy['modifier'], true);
                    if (is_array($decodedModifiers)) {
                        $modifiers = $decodedModifiers;
                    }
                }

                $thenCondition = $this->buildThenCondition(
                    $priceColumn,
                    $modifiers,
                    (float)$sellingPolicy['vat'],
                    (int)($sellingPolicy['price_rounding'] ?? PriceRounder::PRICE_ROUNDING_NONE)
                );
            } catch (\Ess\M2ePro\Model\ChangeTracker\Exceptions\ChangeTrackerException $exception) {
                $this->logger->warning($exception->getMessage(), [
                    'selling_policy_data' => $sellingPolicy,
                    'channel' => $this->channel,
                ]);

                continue;
            }

            $queryData[] = [
                'when' => (int)$sellingPolicy['id'],
                'then' => $thenCondition,
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
            return (string)(new \Zend_Db_Expr(0));
        }

        return "CASE $caseBody END";
    }

    /**
     * @throws \Exception
     */
    protected function getPriceColumnCondition(int $mode, string $attributeCode): string
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
            $attributeQuery = $this
                ->attributesQueryBuilder
                ->getQueryForAttribute($attributeCode, 'product.store_id', 'product.product_id');

            $condition = "( $attributeQuery )";

            if (
                $this->magentoAttributes->isFrontendInputPrice($attributeCode)
                && $this->moduleConfiguration->isEnableMagentoAttributePriceTypeConvertingMode()
            ) {
                $condition .= ' * product.currency_rate';
            }

            return $condition;
        }

        throw new \Ess\M2ePro\Model\ChangeTracker\Exceptions\ChangeTrackerException(
            sprintf(
                'Wrong selling policy mode %s for channel %s',
                $mode,
                $this->channel
            )
        );
    }

    protected function buildThenCondition(
        string $priceColumn,
        array $modifiers,
        float $vat,
        int $priceRounding
    ): string {
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
                $sql = "( $sql + ($attrQuery) )";
                continue;
            }

            if ($mode === \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODIFIER_ATTRIBUTE_DECREASE) {
                $sql = "( $sql - ($attrQuery) )";
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
