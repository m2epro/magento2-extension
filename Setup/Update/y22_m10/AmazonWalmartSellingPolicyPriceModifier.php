<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m10;

class AmazonWalmartSellingPolicyPriceModifier extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    private const PRICE_COEFFICIENT_ABSOLUTE_INCREASE = 1;
    private const PRICE_COEFFICIENT_ABSOLUTE_DECREASE = 2;
    private const PRICE_COEFFICIENT_PERCENTAGE_INCREASE = 3;
    private const PRICE_COEFFICIENT_PERCENTAGE_DECREASE = 4;

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute()
    {
        $this->processAmazon();
        $this->processWalmart();
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function processAmazon()
    {
        $amazonSellingTemplateTableName = $this->installer->getTable('m2epro_amazon_template_selling_format');
        $amazonSellingTemplateTableModifier = $this->getTableModifier('amazon_template_selling_format');

        if ($amazonSellingTemplateTableModifier->isColumnExists('regular_price_modifier')) {
            return;
        }

        $amazonSellingTemplateTableModifier
             ->addColumn(
                 'regular_price_modifier',
                 'TEXT',
                 'NULL',
                 'regular_price_coefficient'
             )
            ->addColumn(
                'regular_sale_price_modifier',
                'TEXT',
                'NULL',
                'regular_sale_price_coefficient'
            )
            ->addColumn(
                'business_price_modifier',
                'TEXT',
                'NULL',
                'business_price_coefficient'
            )
            ->addColumn(
                'business_discounts_tier_modifier',
                'TEXT',
                'NULL',
                'business_discounts_tier_coefficient'
            );

        $select = $this->installer->getConnection()
            ->select()
            ->from($amazonSellingTemplateTableName);
        $query = $select->query();

        while ($row = $query->fetch()) {
            $regularPriceModifier = $this->prepareModifier($row['regular_price_coefficient']);
            $regularSalePriceModifier = $this->prepareModifier($row['regular_sale_price_coefficient']);
            $businessPriceModifier = $this->prepareModifier($row['business_price_coefficient']);
            $businessDiscountsTierModifier = $this->prepareModifier($row['business_discounts_tier_coefficient']);

            $this->getConnection()->update(
                $amazonSellingTemplateTableName,
                [
                    'regular_price_modifier' => json_encode(
                        $regularPriceModifier ? [$regularPriceModifier] : []
                    ),
                    'regular_sale_price_modifier' => json_encode(
                        $regularSalePriceModifier ? [$regularSalePriceModifier] : []
                    ),
                    'business_price_modifier' => json_encode(
                        $businessPriceModifier ? [$businessPriceModifier] : []
                    ),
                    'business_discounts_tier_modifier' => json_encode(
                        $businessDiscountsTierModifier ? [$businessDiscountsTierModifier] : []
                    ),
                ],
                ['template_selling_format_id = ?' => $row['template_selling_format_id']]
            );
        }

        $amazonSellingTemplateTableModifier
            ->dropColumn('regular_price_coefficient')
            ->dropColumn('regular_sale_price_coefficient')
            ->dropColumn('business_price_coefficient')
            ->dropColumn('business_discounts_tier_coefficient');
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Setup
     * @throws \Zend_Db_Adapter_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    private function processWalmart()
    {
        $walmartSellingTemplateTableName = $this->installer->getTable('m2epro_walmart_template_selling_format');
        $walmartSellingTemplateTableModifier = $this->getTableModifier('walmart_template_selling_format');

        if ($walmartSellingTemplateTableModifier->isColumnExists('price_modifier')) {
            return;
        }

        $walmartSellingTemplateTableModifier
            ->addColumn(
                'price_modifier',
                'TEXT',
                'NULL',
                'price_coefficient'
            );

        $select = $this->installer->getConnection()
            ->select()
            ->from($walmartSellingTemplateTableName);
        $query = $select->query();

        while ($row = $query->fetch()) {
            $priceModifier = $this->prepareModifier($row['price_coefficient']);

            $this->getConnection()->update(
                $walmartSellingTemplateTableName,
                [
                    'price_modifier' => json_encode(
                        $priceModifier ? [$priceModifier] : []
                    ),
                ],
                ['template_selling_format_id = ?' => $row['template_selling_format_id']]
            );
        }

        $walmartSellingTemplateTableModifier
            ->dropColumn('price_coefficient');
    }

    /**
     * @param string|null $coefficient
     *
     * @return array
     */
    private function prepareModifier(?string $coefficient): array
    {
        if (is_string($coefficient)) {
            $coefficient = trim($coefficient);
        }

        if (!$coefficient) {
            return [];
        }

        if (strpos($coefficient, '%') !== false) {
            $coefficient = str_replace('%', '', $coefficient);

            if (preg_match('/^[+-]/', $coefficient)) {
                if ($coefficient > 0) {
                    return [
                        'mode' => self::PRICE_COEFFICIENT_PERCENTAGE_INCREASE,
                        'value' => (float)$coefficient
                    ];
                } else {
                    return [
                        'mode' => self::PRICE_COEFFICIENT_PERCENTAGE_DECREASE,
                        'value' => -(float)$coefficient
                    ];
                }
            }

            if ($coefficient > 100) {
                return [
                    'mode' => self::PRICE_COEFFICIENT_PERCENTAGE_INCREASE,
                    'value' => (float)$coefficient - 100
                ];
            } else {
                return [
                    'mode' => self::PRICE_COEFFICIENT_PERCENTAGE_DECREASE,
                    'value' => -((float)$coefficient - 100)
                ];
            }
        }

        if (preg_match('/^[+-]/', $coefficient)) {
            if ($coefficient > 0) {
                return [
                    'mode' => self::PRICE_COEFFICIENT_ABSOLUTE_INCREASE,
                    'value' => (float)$coefficient
                ];
            } else {
                return [
                    'mode' => self::PRICE_COEFFICIENT_ABSOLUTE_DECREASE,
                    'value' => -(float)$coefficient
                ];
            }
        }

        if ($coefficient > 1) {
            return [
                'mode' => self::PRICE_COEFFICIENT_PERCENTAGE_INCREASE,
                'value' => ((float)$coefficient - 1) * 100
            ];
        } else {
            return [
                'mode' => self::PRICE_COEFFICIENT_PERCENTAGE_DECREASE,
                'value' => -((float)$coefficient - 1) * 100
            ];
        }
    }
}
