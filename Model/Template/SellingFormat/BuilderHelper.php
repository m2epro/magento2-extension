<?php

namespace Ess\M2ePro\Model\Template\SellingFormat;

use Ess\M2ePro\Model\Template\SellingFormat as SellingFormat;

class BuilderHelper
{
    /**
     * @param string $prefix
     * @param array $input
     *
     * @return array
     */
    public static function getPriceModifierData(string $prefix, array $input): array
    {
        $keyPriceModifierMode = $prefix . "_modifier_mode";
        $keyPriceModifierValue = $prefix . "_modifier_value";
        $keyPriceModifierAttribute = $prefix . "_modifier_attribute";

        if (
            empty($input[$keyPriceModifierMode])
            || !is_array($input[$keyPriceModifierMode])
        ) {
            return [];
        }

        $priceModifierData = [];
        foreach ($input[$keyPriceModifierMode] as $key => $priceModifierMode) {
            if (
                $priceModifierMode == SellingFormat::PRICE_MODIFIER_ATTRIBUTE_INCREASE
                || $priceModifierMode == SellingFormat::PRICE_MODIFIER_ATTRIBUTE_DECREASE
                || $priceModifierMode == SellingFormat::PRICE_MODIFIER_ATTRIBUTE_PERCENTAGE_INCREASE
                || $priceModifierMode == SellingFormat::PRICE_MODIFIER_ATTRIBUTE_PERCENTAGE_DECREASE
            ) {
                if (
                    !isset($input[$keyPriceModifierAttribute][$key])
                    || !is_string($input[$keyPriceModifierAttribute][$key])
                ) {
                    continue;
                }

                $priceModifierData[] = [
                    'mode' => $priceModifierMode,
                    'attribute_code' => $input[$keyPriceModifierAttribute][$key],
                ];

                continue;
            }

            if (
                !isset($input[$keyPriceModifierValue][$key])
                || !is_string($input[$keyPriceModifierValue][$key])
            ) {
                continue;
            }

            $priceModifierData[] = [
                'mode' => $priceModifierMode,
                'value' => $input[$keyPriceModifierValue][$key],
            ];
        }

        return $priceModifierData;
    }
}
