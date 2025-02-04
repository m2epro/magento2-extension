<?php

namespace Ess\M2ePro\Model\Ebay\Listing\Product;

use Ess\M2ePro\Model\Ebay\Template\SellingFormat;

class PriceCalculator extends \Ess\M2ePro\Model\Listing\Product\PriceCalculator
{
    protected function isPriceVariationModeParent()
    {
        return $this->getPriceVariationMode() == SellingFormat::PRICE_VARIATION_MODE_PARENT;
    }

    protected function isPriceVariationModeChildren()
    {
        return $this->getPriceVariationMode() == SellingFormat::PRICE_VARIATION_MODE_CHILDREN;
    }

    protected function prepareOptionTitles($optionTitles)
    {
        foreach ($optionTitles as &$optionTitle) {
            $optionTitle = \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Option::formatOptionValue(
                (string)$optionTitle
            );
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

    protected function getCurrencyForPriceConvert()
    {
        return $this->getComponentListing()->getEbayMarketplace()->getCurrency();
    }
}
