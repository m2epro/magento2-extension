<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher;

class Attribute extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
    private $magentoProduct = null;

    private $sourceAttributes = array();

    private $destinationAttributes = array();

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Attribute\Resolver $resolver */
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
        $this->sourceAttributes = array();

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
        $this->magentoProduct   = null;

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
        $this->matchedAttributes     = null;

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
        if (is_null($this->matchedAttributes)) {
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
        return count($this->getMagentoUnmatchedAttributes()) <= 0 && count($this->getChannelUnmatchedAttributes()) <= 0;
    }

    /**
     * @return bool
     */
    public function isNotMatched()
    {
        return count($this->getMatchedAttributes()) <= 0;
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
        if (!is_null($this->magentoProduct) && $this->magentoProduct->isGroupedType() &&
            !$this->magentoProduct->getVariationVirtualAttributes()
        ) {
            $channelAttribute = reset($this->destinationAttributes);

            $this->matchedAttributes = array(
                \Ess\M2ePro\Model\Magento\Product\Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL => $channelAttribute
            );

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
                $attribute, $this->prepareAttributeNames($attribute)
            );
        }

        $this->getResolver()->clearDestinationAttributes();

        foreach ($this->getDestinationAttributes() as $attribute) {
            $this->getResolver()->addDestinationAttribute(
                $attribute, $this->prepareAttributeNames($attribute)
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
                $attribute, $this->prepareAttributeNames($attribute, $names)
            );
        }

        $this->getResolver()->clearDestinationAttributes();

        foreach ($this->getDestinationAttributesLocalVocabularyData() as $attribute => $names) {
            $this->getResolver()->addDestinationAttribute(
                $attribute, $this->prepareAttributeNames($attribute, $names)
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
                $attribute, $this->prepareAttributeNames($attribute, $names)
            );
        }

        $this->getResolver()->clearDestinationAttributes();

        foreach ($this->getDestinationAttributesServerVocabularyData() as $attribute => $names) {
            $this->getResolver()->addDestinationAttribute(
                $attribute, $this->prepareAttributeNames($attribute, $names)
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

        if (!is_null($this->magentoProduct)) {
            $magentoVariations = $this->magentoProduct
                ->getVariationInstance()
                ->getVariationsTypeStandard();

            $this->sourceAttributes = array_keys($magentoVariations['set']);
        }

        return $this->sourceAttributes;
    }

    private function getSourceAttributesData()
    {
        if (!is_null($this->magentoProduct)) {
            $magentoAttributesNames = $this->magentoProduct
                ->getVariationInstance()
                ->getTitlesVariationSet();

            $magentoStandardVariations = $this->magentoProduct
                ->getVariationInstance()
                ->getVariationsTypeStandard();

            $resultData = array();
            foreach (array_keys($magentoStandardVariations['set']) as $attribute) {
                $titles = array();
                if (isset($magentoAttributesNames[$attribute])) {
                    $titles = $magentoAttributesNames[$attribute]['titles'];
                }

                $resultData[$attribute] = $titles;
            }

            return $resultData;
        }

        return array_fill_keys($this->getSourceAttributes(), array());
    }

    private function getDestinationAttributes()
    {
        return $this->destinationAttributes;
    }

    private function getDestinationAttributesLocalVocabularyData()
    {
        $vocabularyHelper = $this->getHelper('Component\Amazon\Vocabulary');

        $resultData = array();
        foreach ($this->getDestinationAttributes() as $attribute) {
            $resultData[$attribute] = $vocabularyHelper->getLocalAttributeNames($attribute);
        }

        return $resultData;
    }

    private function getDestinationAttributesServerVocabularyData()
    {
        $vocabularyHelper = $this->getHelper('Component\Amazon\Vocabulary');

        $resultData = array();
        foreach ($this->getDestinationAttributes() as $attribute) {
            $resultData[$attribute] = $vocabularyHelper->getServerAttributeNames($attribute);
        }

        return $resultData;
    }

    // ---------------------------------------

    private function getResolver()
    {
        if (!is_null($this->resolver)) {
            return $this->resolver;
        }

        $this->resolver = $this->modelFactory->getObject('Amazon\Listing\Product\Variation\Matcher\Attribute\Resolver');
        return $this->resolver;
    }

    private function prepareAttributeNames($attribute, array $names = array())
    {
        $names[] = $attribute;
        $names = array_unique($names);

        $names = array_map('trim', $names);
        $names = array_map('strtolower', $names);

        return $names;
    }

    //########################################
}