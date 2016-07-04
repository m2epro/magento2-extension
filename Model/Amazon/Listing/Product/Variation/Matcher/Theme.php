<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher;

class Theme extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
    private $magentoProduct = null;

    private $sourceAttributes = array();

    private $themes = array();

    private $matchedTheme = null;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $product
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $product)
    {
        $this->magentoProduct   = $product;
        $this->sourceAttributes = array();

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
        if (is_null($this->matchedTheme)) {
            $this->match();
        }

        return $this->matchedTheme;
    }

    //########################################

    private function match()
    {
        $this->validate();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Attribute $attributeMatcher */
        $attributeMatcher = $this->modelFactory->getObject('Amazon\Listing\Product\Variation\Matcher\Attribute');

        if (!is_null($this->magentoProduct)) {
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
        if (is_null($this->magentoProduct) && empty($this->sourceAttributes)) {
            throw new \Ess\M2ePro\Model\Exception('Magento Product and Channel Attributes were not set.');
        }
    }

    //########################################
}