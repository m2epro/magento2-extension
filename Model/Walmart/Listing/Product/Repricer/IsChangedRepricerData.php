<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Repricer;

class IsChangedRepricerData
{
    public function execute(\Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct): bool
    {
        return $this->isChangedStrategyName($walmartListingProduct)
            || $this->isChangedMinPrice($walmartListingProduct)
            || $this->isChangedMaxPrice($walmartListingProduct);
    }

    private function isChangedStrategyName(\Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct): bool
    {
        $onlineValue = $walmartListingProduct->getOnlineRepricerStrategyName();

        $repricerTemplateSource = $walmartListingProduct->getRepricerTemplateSource();
        $currentValue = $repricerTemplateSource !== null
            ? $repricerTemplateSource->getStrategyName()
            : null;

        return $onlineValue !== $currentValue;
    }

    private function isChangedMinPrice(\Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct): bool
    {
        $onlineValue = $walmartListingProduct->getOnlineRepricerMinPrice();

        $repricerTemplateSource = $walmartListingProduct->getRepricerTemplateSource();
        $currentValue = $repricerTemplateSource !== null
            ? $repricerTemplateSource->getRepricerMinPrice()
            : null;

        return $onlineValue !== $currentValue;
    }

    private function isChangedMaxPrice(\Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct): bool
    {
        $onlineValue = $walmartListingProduct->getOnlineRepricerMaxPrice();

        $repricerTemplateSource = $walmartListingProduct->getRepricerTemplateSource();
        $currentValue = $repricerTemplateSource !== null
            ? $repricerTemplateSource->getRepricerMaxPrice()
            : null;

        return $onlineValue !== $currentValue;
    }
}
