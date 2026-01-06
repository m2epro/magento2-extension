<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

class VariationCountValidator
{
    public function execute(\Ess\M2ePro\Model\Magento\Product $magentoProduct): bool
    {
        $variationSuite = $magentoProduct->getVariationInstance()->getVariationsStandardSuite();
        $variations = $variationSuite->getVariations();

        return count($variations['variations'])
            <= \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Validator::MAX_EBAY_VARIATIONS_COUNT;
    }
}
