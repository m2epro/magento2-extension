<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product;

use Ess\M2ePro\Model\Ebay\Template\SellingFormat;
use Ess\M2ePro\Model\Listing\Product\Variation as ListingProductVariation;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\PriceCalculator
 */
class PriceCalculator extends \Ess\M2ePro\Model\Listing\Product\PriceCalculator
{
    //########################################

    protected function isPriceVariationModeParent()
    {
        return $this->getPriceVariationMode() == SellingFormat::PRICE_VARIATION_MODE_PARENT;
    }

    protected function isPriceVariationModeChildren()
    {
        return $this->getPriceVariationMode() == SellingFormat::PRICE_VARIATION_MODE_CHILDREN;
    }

    //########################################

    public function getVariationValue(ListingProductVariation $variation)
    {
        if ($variation->getChildObject()->isDelete()) {
            return 0;
        }

        return parent::getVariationValue($variation);
    }

    //########################################

    protected function prepareOptionTitles($optionTitles)
    {
        foreach ($optionTitles as &$optionTitle) {
            $optionTitle = trim($this->helperFactory->getObject('Data')->reduceWordsInString(
                $optionTitle,
                \Ess\M2ePro\Helper\Component\Ebay::MAX_LENGTH_FOR_OPTION_VALUE
            ));
        }

        return $optionTitles;
    }

    protected function prepareAttributeTitles($attributeTitles)
    {
        foreach ($attributeTitles as &$attributeTitle) {
            $attributeTitle = trim($attributeTitle);
        }

        return $attributeTitles;
    }

    //########################################

    protected function getCurrencyForPriceConvert()
    {
        return $this->getComponentListing()->getEbayMarketplace()->getCurrency();
    }

    //########################################
}
