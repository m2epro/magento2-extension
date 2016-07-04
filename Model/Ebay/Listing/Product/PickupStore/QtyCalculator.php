<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\PickupStore;

class QtyCalculator extends \Ess\M2ePro\Model\Ebay\Listing\Product\QtyCalculator
{
    //########################################

    public function getLocationProductValue(\Ess\M2ePro\Model\Ebay\Account\PickupStore $accountPickupStore,
                                            $bufferedClearValue = NULL)
    {
        if (!$accountPickupStore->isQtyModeSellingFormatTemplate()) {
            $this->source = $accountPickupStore->getQtySource();
        }

        if (!is_null($bufferedClearValue)) {
            $value = $bufferedClearValue;
        } else {
            $value = $this->getClearLocationProductValue($accountPickupStore);
        }

        $value = $this->applySellingFormatTemplateModifications($value);
        $value < 0 && $value = 0;

        return $value;
    }

    public function getLocationVariationValue(\Ess\M2ePro\Model\Listing\Product\Variation $variation,
                                              \Ess\M2ePro\Model\Ebay\Account\PickupStore $accountPickupStore,
                                              $bufferedClearValue = NULL)
    {
        if (!$accountPickupStore->isQtyModeSellingFormatTemplate()) {
            $this->source = $accountPickupStore->getQtySource();
        }

        if (!is_null($bufferedClearValue)) {
            $value = $bufferedClearValue;
        } else {
            $value = $this->getClearLocationVariationValue($variation, $accountPickupStore);
        }

        $value = $this->applySellingFormatTemplateModifications($value);
        $value < 0 && $value = 0;

        return $value;
    }

    //########################################

    public function getClearLocationProductValue(\Ess\M2ePro\Model\Ebay\Account\PickupStore $accountPickupStore)
    {
        if (!$accountPickupStore->isQtyModeSellingFormatTemplate()) {
            $this->source = $accountPickupStore->getQtySource();
        }

        return $this->getClearProductValue();
    }

    public function getClearLocationVariationValue(\Ess\M2ePro\Model\Listing\Product\Variation $variation,
                                                   \Ess\M2ePro\Model\Ebay\Account\PickupStore $accountPickupStore)
    {
        if (!$accountPickupStore->isQtyModeSellingFormatTemplate()) {
            $this->source = $accountPickupStore->getQtySource();
        }

        return $this->getClearVariationValue($variation);
    }

    //########################################
}