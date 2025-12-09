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
        $currentValue = $walmartListingProduct
            ->getWalmartSellingFormatTemplate()
            ->getRepricerStrategyByAccountId((int)$walmartListingProduct->getAccount()->getId());

        return $onlineValue !== $currentValue;
    }

    private function isChangedMinPrice(\Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct): bool
    {
        $onlineValue = $walmartListingProduct->getOnlineRepricerMinPrice();
        $currentValue = $walmartListingProduct->getSellingFormatTemplateSource()->getRepricerMinPrice();

        return $onlineValue !== $currentValue;
    }

    private function isChangedMaxPrice(\Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct): bool
    {
        $onlineValue = $walmartListingProduct->getOnlineRepricerMaxPrice();
        $currentValue = $walmartListingProduct->getSellingFormatTemplateSource()->getRepricerMaxPrice();

        return $onlineValue !== $currentValue;
    }
}
