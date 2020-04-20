<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Attribute
 */
class Attribute extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
    private $magentoProduct = null;

    private $sourceAttributes = [];

    private $destinationAttributes = [];

    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Attribute\Resolver $resolver */
    private $resolver = null;

    private $matchedAttributes = null;

    private $canUseDictionary = true;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $product
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $product)
    {
        $this->magentoProduct = $product;
        $this->sourceAttributes = [];

        $this->matchedAttributes = null;

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
        $this->magentoProduct = null;

        $this->matchedAttributes = null;

        return $this;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setDestinationAttributes(array $attributes)
    {
        $this->destinationAttributes = $attributes;
        $this->matchedAttributes = null;

        return $this;
    }

    // ---------------------------------------

    /**
     * @param bool $flag
     * @return $this
     */
    public function canUseDictionary($flag = true)
    {
        $this->canUseDictionary = $flag;
        return $this;
    }

    //########################################

    /**
     * @return bool
     */
    public function isAmountEqual()
    {
        return count($this->getSourceAttributes()) == count($this->getDestinationAttributes());
    }

    /**
     * @return bool
     */
    public function isSourceAmountGreater()
    {
        return count($this->getSourceAttributes()) > count($this->getDestinationAttributes());
    }

    /**
     * @return bool
     */
    public function isDestinationAmountGreater()
    {
        return count($this->getSourceAttributes()) < count($this->getDestinationAttributes());
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getMatchedAttributes()
    {
        if ($this->matchedAttributes === null) {
            $this->match();
        }

        return $this->matchedAttributes;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isFullyMatched()
    {
        return empty($this->getMagentoUnmatchedAttributes()) && empty($this->getChannelUnmatchedAttributes());
    }

    /**
     * @return bool
     */
    public function isNotMatched()
    {
        return empty($this->getMatchedAttributes());
    }

    /**
     * @return bool
     */
    public function isPartiallyMatched()
    {
        return !$this->isFullyMatched() && !$this->isNotMatched();
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getMagentoUnmatchedAttributes()
    {
        return array_keys($this->getMatchedAttributes(), null);
    }

    /**
     * @return array
     */
    public function getChannelUnmatchedAttributes()
    {
        $matchedChannelAttributes = array_values($this->getMatchedAttributes());
        return array_diff($this->destinationAttributes, $matchedChannelAttributes);
    }

    //########################################

    private function match()
    {
        if ($this->magentoProduct !== null && $this->magentoProduct->isGroupedType() &&
            !$this->magentoProduct->getVariationVirtualAttributes()
        ) {
            $channelAttribute = reset($this->destinationAttributes);

            $this->matchedAttributes = [
                \Ess\M2ePro\Model\Magento\Product\Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL => $channelAttribute
            ];

            return;
        }

        if ($this->matchByNames()) {
            return;
        }

        if (!$this->canUseDictionary) {
            return;
        }

        if ($this->matchByLocalVocabulary()) {
            return;
        }

        $this->matchByServerVocabulary();
    }

    private function matchByNames()
    {
        $this->getResolver()->clearSourceAttributes();

        foreach ($this->getSourceAttributes() as $attribute) {
            $this->getResolver()->addSourceAttribute(
                $attribute,
                $this->prepareAttributeNames($attribute)
            );
        }

        $this->getResolver()->clearDestinationAttributes();

        foreach ($this->getDestinationAttributes() as $attribute) {
            $this->getResolver()->addDestinationAttribute(
                $attribute,
                $this->prepareAttributeNames($attribute)
            );
        }

        $this->matchedAttributes = $this->getResolver()->resolve()->getResolvedAttributes();

        return $this->isFullyMatched();
    }

    private function matchByLocalVocabulary()
    {
        $this->getResolver()->clearSourceAttributes();

        foreach ($this->getSourceAttributesData() as $attribute => $names) {
            $this->getResolver()->addSourceAttribute(
                $attribute,
                $this->prepareAttributeNames($attribute, $names)
            );
        }

        $this->getResolver()->clearDestinationAttributes();

        foreach ($this->getDestinationAttributesLocalVocabularyData() as $attribute => $names) {
            $this->getResolver()->addDestinationAttribute(
                $attribute,
                $this->prepareAttributeNames($attribute, $names)
            );
        }

        $this->matchedAttributes = $this->getResolver()->resolve()->getResolvedAttributes();

        return $this->isFullyMatched();
    }

    private function matchByServerVocabulary()
    {
        $this->getResolver()->clearSourceAttributes();

        foreach ($this->getSourceAttributesData() as $attribute => $names) {
            $this->getResolver()->addSourceAttribute(
                $attribute,
                $this->prepareAttributeNames($attribute, $names)
            );
        }

        $this->getResolver()->clearDestinationAttributes();

        foreach ($this->getDestinationAttributesServerVocabularyData() as $attribute => $names) {
            $this->getResolver()->addDestinationAttribute(
                $attribute,
                $this->prepareAttributeNames($attribute, $names)
            );
        }

        $this->matchedAttributes = $this->getResolver()->resolve()->getResolvedAttributes();

        return $this->isFullyMatched();
    }

    //########################################

    private function getSourceAttributes()
    {
        if (!empty($this->sourceAttributes)) {
            return $this->sourceAttributes;
        }

        if ($this->magentoProduct !== null) {
            $magentoVariations = $this->magentoProduct
                ->getVariationInstance()
                ->getVariationsTypeStandard();

            $this->sourceAttributes = array_keys($magentoVariations['set']);
        }

        return $this->sourceAttributes;
    }

    private function getSourceAttributesData()
    {
        if ($this->magentoProduct !== null) {
            $magentoAttributesNames = $this->magentoProduct
                ->getVariationInstance()
                ->getTitlesVariationSet();

            $magentoStandardVariations = $this->magentoProduct
                ->getVariationInstance()
                ->getVariationsTypeStandard();

            $resultData = [];
            foreach (array_keys($magentoStandardVariations['set']) as $attribute) {
                $titles = [];
                if (isset($magentoAttributesNames[$attribute])) {
                    $titles = $magentoAttributesNames[$attribute]['titles'];
                }

                $resultData[$attribute] = $titles;
            }

            return $resultData;
        }

        return array_fill_keys($this->getSourceAttributes(), []);
    }

    private function getDestinationAttributes()
    {
        return $this->destinationAttributes;
    }

    private function getDestinationAttributesLocalVocabularyData()
    {
        $vocabularyHelper = $this->getHelper('Component_Walmart_Vocabulary');

        $resultData = [];
        foreach ($this->getDestinationAttributes() as $attribute) {
            $resultData[$attribute] = $vocabularyHelper->getLocalAttributeNames($attribute);
        }

        return $resultData;
    }

    private function getDestinationAttributesServerVocabularyData()
    {
        $vocabularyHelper = $this->getHelper('Component_Walmart_Vocabulary');

        $resultData = [];
        foreach ($this->getDestinationAttributes() as $attribute) {
            $resultData[$attribute] = $vocabularyHelper->getServerAttributeNames($attribute);
        }

        return $resultData;
    }

    // ---------------------------------------

    private function getResolver()
    {
        if ($this->resolver !== null) {
            return $this->resolver;
        }

        $this->resolver =
            $this->modelFactory->getObject('Walmart_Listing_Product_Variation_Matcher_Attribute_Resolver');
        return $this->resolver;
    }

    private function prepareAttributeNames($attribute, array $names = [])
    {
        $names[] = $attribute;
        $names = array_unique($names);

        $names = array_map('trim', $names);
        $names = array_map('strtolower', $names);

        return $names;
    }

    //########################################
}
