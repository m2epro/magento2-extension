<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Repricing;

use \Ess\M2ePro\Model\Amazon\Account\Repricing as AccountRepricing;
use Ess\M2ePro\Model\Listing\Product\Variation;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing\PriceCalculator
 */
class PriceCalculator extends \Ess\M2ePro\Model\Listing\Product\PriceCalculator
{
    //########################################

    protected function isPriceVariationModeParent()
    {
        return $this->getPriceVariationMode() == AccountRepricing::PRICE_VARIATION_MODE_PARENT;
    }

    protected function isPriceVariationModeChildren()
    {
        return $this->getPriceVariationMode() == AccountRepricing::PRICE_VARIATION_MODE_CHILDREN;
    }

    //########################################

    public function getProductValue()
    {
        if ($this->isSourceModeNone()) {
            return null;
        }

        return parent::getProductValue();
    }

    public function getVariationValue(Variation $variation)
    {
        if ($this->isSourceModeNone()) {
            return null;
        }

        return parent::getVariationValue($variation);
    }

    //########################################

    protected function getCurrencyForPriceConvert()
    {
        return $this->getComponentListing()->getAmazonMarketplace()->getDefaultCurrency();
    }

    //########################################
}
