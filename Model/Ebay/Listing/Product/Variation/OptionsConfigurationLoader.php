<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Variation;

use Ess\M2ePro\Model\Ebay\Listing\Product\Variation\OptionsConfigurationLoader\Option;
use Ess\M2ePro\Model\Ebay\Listing\Product\Variation\OptionsConfigurationLoader\Variation;
use Zend_Db_Expr;

class OptionsConfigurationLoader
{
    private \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $ebayCategoryHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $ebayCategoryHelper
    ) {
        $this->ebayCategoryHelper = $ebayCategoryHelper;
    }

    public function execute(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $temporaryResult = [];
        foreach ($listingProduct->getVariations(true) as $variation) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
            $ebayVariation = $variation->getChildObject();
            if ($ebayVariation->isDelete()) {
                continue;
            }

            foreach ($variation->getOptions(true) as $option) {
                if (isset($temporaryResult[$option->getAttribute()][$option->getOption()])) {
                    continue;
                }

                $temporaryResult[$option->getAttribute()][$option->getOption()] = new Option(
                    $option->getOption(),
                    $option->getChannelOption(),
                    !empty($option->getChannelOption(false))
                );
            }
        }

        $attributes = array_keys($temporaryResult);

        $result = [];
        foreach ($attributes as $attribute) {
            $availableOptions = $this->getAvailableSpecificValues($listingProduct, $attribute);
            if (empty($availableOptions)) {
                continue;
            }

            $result[] = new Variation(
                $attribute,
                array_values($temporaryResult[$attribute]),
                $availableOptions
            );
        }

        return $result;
    }

    private function getAvailableSpecificValues(\Ess\M2ePro\Model\Listing\Product $listingProduct, string $specificName)
    {
        $specifics = $this->getSpecifics($listingProduct);
        $specificName = $this->getSpecificName($listingProduct, $specificName);

        foreach ($specifics as $specific) {
            if ($specific['id'] !== $specificName) {
                continue;
            }

            return array_map(
                fn($value) => $value['value'],
                $specific['values']
            );
        }

        return [];
    }

    private function getSpecifics(\Ess\M2ePro\Model\Listing\Product $listingProduct): array
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $childListingProduct */
        $childListingProduct = $listingProduct->getChildObject();

        $specifics = $this->ebayCategoryHelper->getSpecifics(
            $childListingProduct->getCategoryTemplate()->getCategoryId(),
            (int)$listingProduct->getMarketplace()->getId()
        );

        if (!is_array($specifics)) {
            return [];
        }

        return $specifics;
    }

    private function getSpecificName(\Ess\M2ePro\Model\Listing\Product $listingProduct, string $specificName)
    {
        $replacements = $this->getReplacements($listingProduct);
        if (empty($replacements)) {
            return $specificName;
        }

        if (isset($replacements[$specificName])) {
            return $replacements[$specificName];
        }

        return $specificName;
    }

    private function getReplacements(\Ess\M2ePro\Model\Listing\Product $listingProduct): array
    {
        $confAttributes = [];
        $additionalData = $listingProduct->getAdditionalData();
        if (!empty($additionalData['configurable_attributes'])) {
            $confAttributes = $additionalData['configurable_attributes'];
        }

        if (empty($confAttributes)) {
            return [];
        }

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $childListingProduct */
        $childListingProduct = $listingProduct->getChildObject();

        $replacements = [];
        foreach ($childListingProduct->getCategoryTemplate()->getSpecifics(true) as $specific) {
            if (!$specific->isItemSpecificsMode() || !$specific->isCustomAttributeValueMode()) {
                continue;
            }

            $attrCode = trim($specific->getData('value_custom_attribute'));
            $attrTitle = trim($specific->getData('attribute_title'));

            if (!array_key_exists($attrCode, $confAttributes) || $confAttributes[$attrCode] == $attrTitle) {
                continue;
            }

            $replacements[$confAttributes[$attrCode]] = $attrTitle;
        }

        return $replacements;
    }
}
