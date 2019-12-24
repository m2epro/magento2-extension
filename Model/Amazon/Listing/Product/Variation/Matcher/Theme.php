<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Theme
 */
class Theme extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
    private $magentoProduct = null;

    private $sourceAttributes = [];

    private $themes = [];

    private $matchedTheme = null;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $product
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $product)
    {
        $this->magentoProduct   = $product;
        $this->sourceAttributes = [];

        return $this;
    }

    // ---------------------------------------

    /**
     * @param array $attributes
     * @return $this
     */
    public function setSourceAttributes(array $attributes)
    {
        $this->sourceAttributes = $attributes;
        $this->magentoProduct   = null;

        return $this;
    }

    // ---------------------------------------

    /**
     * @param array $themes
     * @return $this
     */
    public function setThemes(array $themes)
    {
        $this->themes = $themes;
        return $this;
    }

    //########################################

    /**
     * @return mixed
     */
    public function getMatchedTheme()
    {
        if ($this->matchedTheme === null) {
            $this->match();
        }

        return $this->matchedTheme;
    }

    //########################################

    private function match()
    {
        $this->validate();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Attribute $attributeMatcher */
        $attributeMatcher = $this->modelFactory->getObject('Amazon_Listing_Product_Variation_Matcher_Attribute');

        if ($this->magentoProduct !== null) {
            if ($this->magentoProduct->isGroupedType()) {
                $this->matchedTheme = null;
                return $this;
            }

            $attributeMatcher->setMagentoProduct($this->magentoProduct);
        }

        if (!empty($this->sourceAttributes)) {
            $attributeMatcher->setSourceAttributes($this->sourceAttributes);
            $attributeMatcher->canUseDictionary(false);
        }

        foreach ($this->themes as $themeName => $themeAttributes) {
            $attributeMatcher->setDestinationAttributes($themeAttributes['attributes']);

            if ($attributeMatcher->isAmountEqual() && $attributeMatcher->isFullyMatched()) {
                $this->matchedTheme = $themeName;
                break;
            }
        }

        return $this;
    }

    private function validate()
    {
        if ($this->magentoProduct === null && empty($this->sourceAttributes)) {
            throw new \Ess\M2ePro\Model\Exception('Magento Product and Channel Attributes were not set.');
        }
    }

    //########################################
}
