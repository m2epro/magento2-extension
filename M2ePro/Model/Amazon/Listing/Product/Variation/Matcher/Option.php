<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Option
 */
class Option extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Magento\Product $magentoProduct */
    private $magentoProduct = null;

    private $destinationOptions = [];

    private $destinationOptionsLocalVocabularyNames = [];

    private $destinationOptionsServerVocabularyNames = [];

    private $matchedAttributes = [];

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Matcher\Option\Resolver $resolver */
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
//        'B00005N5PF' => array(
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

        $this->destinationOptionsLocalVocabularyNames  = [];
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
    public function getMatchedOptionGeneralId(array $sourceOption)
    {
        $this->validate();

        if ($generalId = $this->matchGeneralIdByNames($sourceOption)) {
            return $generalId;
        }

        if ($generalId = $this->matchGeneralIdByLocalVocabulary($sourceOption)) {
            return $generalId;
        }

        if ($generalId = $this->matchGeneralIdByServerVocabulary($sourceOption)) {
            return $generalId;
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

    private function matchGeneralIdByNames(array $sourceOption)
    {
        $sourceOptionNames = [];
        foreach ($sourceOption as $attribute => $option) {
            $sourceOptionNames[$attribute] = $this->prepareOptionNames($option);
        }

        $this->getResolver()
            ->setSourceOption($sourceOptionNames)
            ->setDestinationOptions($this->destinationOptions)
            ->setMatchedAttributes($this->matchedAttributes);

        return $this->getResolver()->resolve()->getResolvedGeneralId();
    }

    private function matchGeneralIdByLocalVocabulary(array $sourceOption)
    {
        $this->getResolver()
            ->setSourceOption($this->getSourceOptionNames($sourceOption))
            ->setDestinationOptions($this->getDestinationOptionLocalVocabularyNames())
            ->setMatchedAttributes($this->matchedAttributes);

        return $this->getResolver()->resolve()->getResolvedGeneralId();
    }

    private function matchGeneralIdByServerVocabulary(array $sourceOption)
    {
        $this->getResolver()
            ->setSourceOption($this->getSourceOptionNames($sourceOption))
            ->setDestinationOptions($this->getDestinationOptionServerVocabularyNames())
            ->setMatchedAttributes($this->matchedAttributes);

        return $this->getResolver()->resolve()->getResolvedGeneralId();
    }

    // ---------------------------------------

    private function getSourceOptionNames($sourceOption)
    {
        $magentoOptionNames = $this->magentoProduct->getVariationInstance()->getTitlesVariationSet();

        $resultNames = [];
        foreach ($sourceOption as $attribute => $option) {
            $names = [];
            if (isset($magentoOptionNames[$attribute]['values'])) {
                $attributeValues = $magentoOptionNames[$attribute]['values'];
                foreach ($attributeValues as $defaultValue => $optionValues) {
                    if (in_array($option, $optionValues, true)) {
                        $names = $magentoOptionNames[$attribute]['values'][$defaultValue];
                    }
                }
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

        $vocabularyHelper = $this->getHelper('Component_Amazon_Vocabulary');

        foreach ($this->destinationOptions as $generalId => $destinationOption) {
            foreach ($destinationOption as $attributeName => $optionName) {
                $this->destinationOptionsLocalVocabularyNames[$generalId][$attributeName] = $this->prepareOptionNames(
                    $optionName,
                    $vocabularyHelper->getLocalOptionNames($attributeName, $optionName)
                );
            }
        }

        return $this->destinationOptionsLocalVocabularyNames;
    }

    private function getDestinationOptionServerVocabularyNames()
    {
        if (!empty($this->destinationOptionsServerVocabularyNames)) {
            return $this->destinationOptionsServerVocabularyNames;
        }

        $vocabularyHelper = $this->getHelper('Component_Amazon_Vocabulary');

        foreach ($this->destinationOptions as $generalId => $destinationOption) {
            foreach ($destinationOption as $attributeName => $optionName) {
                $this->destinationOptionsServerVocabularyNames[$generalId][$attributeName] = $this->prepareOptionNames(
                    $optionName,
                    $vocabularyHelper->getServerOptionNames($attributeName, $optionName)
                );
            }
        }

        return $this->destinationOptionsServerVocabularyNames;
    }

    //########################################

    private function getResolver()
    {
        if ($this->resolver !== null) {
            return $this->resolver;
        }

        $this->resolver = $this->modelFactory->getObject('Amazon_Listing_Product_Variation_Matcher_Option_Resolver');
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
