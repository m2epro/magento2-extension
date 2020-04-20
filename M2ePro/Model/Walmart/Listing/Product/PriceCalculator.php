<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Walmart\Listing getComponentListing()
 * @method \Ess\M2ePro\Model\Walmart\Template\SellingFormat getComponentSellingFormatTemplate()
 * @method \Ess\M2ePro\Model\Walmart\Listing\Product getComponentProduct()
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\PriceCalculator
 */
class PriceCalculator extends \Ess\M2ePro\Model\Listing\Product\PriceCalculator
{
    //########################################

    protected function isPriceVariationModeParent()
    {
        return $this->getPriceVariationMode()
            == \Ess\M2ePro\Model\Walmart\Template\SellingFormat::PRICE_VARIATION_MODE_PARENT;
    }

    protected function isPriceVariationModeChildren()
    {
        return $this->getPriceVariationMode()
            == \Ess\M2ePro\Model\Walmart\Template\SellingFormat::PRICE_VARIATION_MODE_CHILDREN;
    }

    //########################################

    protected function getCurrencyForPriceConvert()
    {
        return $this->getComponentListing()->getWalmartMarketplace()->getDefaultCurrency();
    }

    //########################################
}
