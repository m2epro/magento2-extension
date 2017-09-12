<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

use Ess\M2ePro\Model\Amazon\Template\SellingFormat;
use Ess\M2ePro\Model\Magento\Product;

class PriceCalculator extends \Ess\M2ePro\Model\Listing\Product\PriceCalculator
{
    /**
     * @var bool
     */
    private $isSalePrice = false;

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

    /**
     * @param bool $value
     * @return PriceCalculator
     */
    public function setIsSalePrice($value)
    {
        $this->isSalePrice = (bool)$value;
        return $this;
    }

    /**
     * @return bool
     */
    protected function getIsSalePrice()
    {
        return $this->isSalePrice;
    }

    //########################################

    protected function applyAdditionalOptionValuesModifications(
        \Ess\M2ePro\Model\Listing\Product\Variation $variation, $value
    ) {
        if ($this->getIsSalePrice() && $value <= 0 && $this->isSourceModeSpecial()) {
            return 0;
        }

        return parent::applyAdditionalOptionValuesModifications($variation, $value);
    }

    //########################################

    protected function getExistedProductSpecialValue(Product $product)
    {
        if ($this->getIsSalePrice() && !$product->isSpecialPriceActual()) {
            return 0;
        }

        return parent::getExistedProductSpecialValue($product);
    }

    protected function getBundleProductDynamicSpecialValue(Product $product)
    {
        if ($this->getIsSalePrice() && !$product->isSpecialPriceActual()) {
            return 0;
        }

        return parent::getBundleProductDynamicSpecialValue($product);
    }

    //########################################

    protected function getCurrencyForPriceConvert()
    {
        return $this->getComponentListing()->getAmazonMarketplace()->getDefaultCurrency();
    }

    //########################################
}