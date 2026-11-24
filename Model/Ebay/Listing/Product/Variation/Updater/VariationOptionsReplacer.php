<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Updater;

use Ess\M2ePro\Model\Magento\Product\Variation\StandardSuite;

class VariationOptionsReplacer
{
    public function replace(StandardSuite $suite, \Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $replacedOptions = $listingProduct->getChildObject()->getReplacedVariationOptions();

        $variations = $suite->getVariations();

        $variations['set'] = $this->replaceOptionsInSet($variations['set'], $replacedOptions);
        $variations['variations'] = $this->replaceOptionsInVariations($variations['variations'], $replacedOptions);

        $suite->setVariations($variations);
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\ReplacedOptionDto[] $replacedOptions
     */
    private function replaceOptionsInSet(array $variationSet, array $replacedOptions): array
    {
        if (empty($replacedOptions)) {
            return $variationSet;
        }

        foreach ($variationSet as $optionTitle => &$optionsSet) {
            foreach ($optionsSet as $key => &$option) {
                $replacedOption = $this->findInReplacedOptions(
                    $optionTitle,
                    $option,
                    $replacedOptions
                );

                $option = $replacedOption !== null
                    ? $replacedOption->channelOption
                    : $option;
            }
        }

        return $variationSet;
    }

    private function replaceOptionsInVariations($variations, array $replacedOptions)
    {
        foreach ($variations as &$variation) {
            foreach ($variation as &$option) {
                $replacedOption = $this->findInReplacedOptions(
                    $option['attribute'],
                    $option['option'],
                    $replacedOptions
                );

                $option['channel_option'] = $replacedOption !== null
                    ? $replacedOption->channelOption
                    : null;
            }
        }

        return $variations;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\ReplacedOptionDto[] $replacedOptions
     */
    private function findInReplacedOptions(
        string $attribute,
        string $option,
        array $replacedOptions
    ): ?\Ess\M2ePro\Model\Ebay\Listing\Product\Variation\ReplacedOptionDto {
        foreach ($replacedOptions as $replacedOption) {
            if (
                $replacedOption->attribute === $attribute
                && $replacedOption->option === $option
            ) {
                return $replacedOption;
            }
        }

        return null;
    }
}
