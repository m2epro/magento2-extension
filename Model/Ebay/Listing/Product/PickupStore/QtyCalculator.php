<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\PickupStore;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\PickupStore\QtyCalculator
 */
class QtyCalculator extends \Ess\M2ePro\Model\Ebay\Listing\Product\QtyCalculator
{
    //########################################

    public function getLocationProductValue(
        \Ess\M2ePro\Model\Ebay\Account\PickupStore $accountPickupStore,
        $bufferedClearValue = null
    ) {
        if (!$accountPickupStore->isQtyModeSellingFormatTemplate()) {
            $this->source = $accountPickupStore->getQtySource();
        }

        if ($bufferedClearValue !== null) {
            $value = $bufferedClearValue;
        } else {
            $value = $this->getClearLocationProductValue($accountPickupStore);
        }

        $value = $this->applySellingFormatTemplateModifications($value);
        $value < 0 && $value = 0;

        return $value;
    }

    public function getLocationVariationValue(
        \Ess\M2ePro\Model\Listing\Product\Variation $variation,
        \Ess\M2ePro\Model\Ebay\Account\PickupStore $accountPickupStore,
        $bufferedClearValue = null
    ) {
        if (!$accountPickupStore->isQtyModeSellingFormatTemplate()) {
            $this->source = $accountPickupStore->getQtySource();
        }

        if ($bufferedClearValue !== null) {
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

    public function getClearLocationVariationValue(
        \Ess\M2ePro\Model\Listing\Product\Variation $variation,
        \Ess\M2ePro\Model\Ebay\Account\PickupStore $accountPickupStore
    ) {
        if (!$accountPickupStore->isQtyModeSellingFormatTemplate()) {
            $this->source = $accountPickupStore->getQtySource();
        }

        return $this->getClearVariationValue($variation);
    }

    //########################################
}
