<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Setup\Update\y22_m06;

class EbayFixedPriceModifier extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    private const PRICE_COEFFICIENT_ABSOLUTE_INCREASE = 1;
    private const PRICE_COEFFICIENT_ABSOLUTE_DECREASE = 2;
    private const PRICE_COEFFICIENT_PERCENTAGE_INCREASE = 3;
    private const PRICE_COEFFICIENT_PERCENTAGE_DECREASE = 4;

    public function execute()
    {
        $this->getTableModifier('ebay_template_selling_format')
             ->addColumn(
                 'fixed_price_modifier',
                 'TEXT',
                 'NULL',
                 'fixed_price_coefficient'
             );

        $select = $this->installer->getConnection()
            ->select()
            ->from($this->installer->getTable('m2epro_ebay_template_selling_format'));
        $query = $select->query();

        while ($row = $query->fetch()) {
            $modifier = $this->prepareModifier($row['fixed_price_coefficient']);
            $this->getConnection()->update(
                $this->getFullTableName('ebay_template_selling_format'),
                [
                    'fixed_price_modifier' => json_encode(
                        $modifier ? [$modifier] : []
                    )
                ],
                ['template_selling_format_id = ?' => $row['template_selling_format_id']]
            );
        }

        $this->getTableModifier('ebay_template_selling_format')
             ->dropColumn('fixed_price_coefficient');
    }

    /**
     * @param string|null $coefficient
     *
     * @return array
     */
    private function prepareModifier($coefficient)
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
