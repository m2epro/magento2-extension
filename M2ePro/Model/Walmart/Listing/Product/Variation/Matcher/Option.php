<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Option
 */
class Option extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
    private $magentoProduct = null;

    private $destinationOptions = [];

    private $destinationOptionsLocalVocabularyNames = [];

    private $destinationOptionsServerVocabularyNames = [];

    private $matchedAttributes = [];

    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Option\Resolver $resolver */
    private $resolver = null;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $this->magentoProduct = $magentoProduct;
        return $this;
    }

    // ---------------------------------------

//    $destinationOptions = array(
//        array(
//          'Color' => 'Red',
//          'Size'  => 'XL',
//        ),
//        ...
//    )

    /**
     * @param array $destinationOptions
     * @return $this
     */
    public function setDestinationOptions(array $destinationOptions)
    {
        $this->destinationOptions = $destinationOptions;

        $this->destinationOptionsLocalVocabularyNames = [];
        $this->destinationOptionsServerVocabularyNames = [];

        return $this;
    }

    /**
     * @param array $matchedAttributes
     * @return $this
     */
    public function setMatchedAttributes(array $matchedAttributes)
    {
        $this->matchedAttributes = $matchedAttributes;
        return $this;
    }

    //########################################

//    $sourceOption = array(
//         'Color' => 'red',
//         'Size'  => 'L'
//    )

    /**
     * @param array $sourceOption
     * @return null|int
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getMatchedOptionId(array $sourceOption)
    {
        $this->validate();

        if ($option = $this->matchOptionByNames($sourceOption)) {
            return $option;
        }

        if ($option = $this->matchOptionByLocalVocabulary($sourceOption)) {
            return $option;
        }

        if ($option = $this->matchOptionByServerVocabulary($sourceOption)) {
            return $option;
        }

        return null;
    }

    //########################################

    private function validate()
    {
        if ($this->magentoProduct === null) {
            throw new \Ess\M2ePro\Model\Exception('Magento Product was not set.');
        }

        if (empty($this->destinationOptions)) {
            throw new \Ess\M2ePro\Model\Exception('Destination Options is empty.');
        }
    }

    // ---------------------------------------

    private function matchOptionByNames(array $sourceOption)
    {
        $sourceOptionNames = [];
        foreach ($sourceOption as $attribute => $option) {
            $sourceOptionNames[$attribute] = $this->prepareOptionNames($option);
        }

        $this->getResolver()
            ->setSourceOption($sourceOptionNames)
            ->setDestinationOptions($this->destinationOptions)
            ->setMatchedAttributes($this->matchedAttributes);

        return $this->getResolver()->resolve()->getResolvedOption();
    }

    private function matchOptionByLocalVocabulary(array $sourceOption)
    {
        $this->getResolver()
            ->setSourceOption($this->getSourceOptionNames($sourceOption))
            ->setDestinationOptions($this->getDestinationOptionLocalVocabularyNames())
            ->setMatchedAttributes($this->matchedAttributes);

        return $this->getResolver()->resolve()->getResolvedOption();
    }

    private function matchOptionByServerVocabulary(array $sourceOption)
    {
        $this->getResolver()
            ->setSourceOption($this->getSourceOptionNames($sourceOption))
            ->setDestinationOptions($this->getDestinationOptionServerVocabularyNames())
            ->setMatchedAttributes($this->matchedAttributes);

        return $this->getResolver()->resolve()->getResolvedOption();
    }

    // ---------------------------------------

    private function getSourceOptionNames($sourceOption)
    {
        $magentoOptionNames = $this->magentoProduct->getVariationInstance()->getTitlesVariationSet();

        $resultNames = [];
        foreach ($sourceOption as $attribute => $option) {
            $names = [];
            if (isset($magentoOptionNames[$attribute])) {
                $names = $magentoOptionNames[$attribute]['values'][$option];
            }

            $resultNames[$attribute] = $this->prepareOptionNames($option, $names);
        }

        return $resultNames;
    }

    private function getDestinationOptionLocalVocabularyNames()
    {
        if (!empty($this->destinationOptionsLocalVocabularyNames)) {
            return $this->destinationOptionsLocalVocabularyNames;
        }

        $vocabularyHelper = $this->getHelper('Component_Walmart_Vocabulary');

        foreach ($this->destinationOptions as $destinationOption) {
            $optionNames = [];

            foreach ($destinationOption as $attributeName => $optionName) {
                $optionNames[$attributeName] = $this->prepareOptionNames(
                    $optionName,
                    $vocabularyHelper->getLocalOptionNames($attributeName, $optionName)
                );
            }

            $this->destinationOptionsServerVocabularyNames[] = $optionNames;
        }

        return $this->destinationOptionsLocalVocabularyNames;
    }

    private function getDestinationOptionServerVocabularyNames()
    {
        if (!empty($this->destinationOptionsServerVocabularyNames)) {
            return $this->destinationOptionsServerVocabularyNames;
        }

        $vocabularyHelper = $this->getHelper('Component_Walmart_Vocabulary');

        foreach ($this->destinationOptions as $destinationOption) {
            $optionNames = [];

            foreach ($destinationOption as $attributeName => $optionName) {
                $optionNames[$attributeName] = $this->prepareOptionNames(
                    $optionName,
                    $vocabularyHelper->getServerOptionNames($attributeName, $optionName)
                );
            }

            $this->destinationOptionsServerVocabularyNames[] = $optionNames;
        }

        return $this->destinationOptionsServerVocabularyNames;
    }

    //########################################

    private function getResolver()
    {
        if ($this->resolver !== null) {
            return $this->resolver;
        }

        $this->resolver = $this->modelFactory->getObject('Walmart_Listing_Product_Variation_Matcher_Option_Resolver');
        return $this->resolver;
    }

    private function prepareOptionNames($option, array $names = [])
    {
        $names[] = $option;
        $names = array_unique($names);

        $names = array_map('trim', $names);
        $names = array_map('strtolower', $names);

        return $names;
    }

    //########################################
}
