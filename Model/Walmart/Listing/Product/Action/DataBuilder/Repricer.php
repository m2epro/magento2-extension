<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder;

class Repricer extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\AbstractModel
{
    public function getBuilderData(): array
    {
        $walmartListingProduct = $this->getWalmartListingProduct();

        $repricerTemplateSource = $walmartListingProduct->getRepricerTemplateSource();

        if (empty($repricerTemplateSource)) {
            if ($this->isNeedRemoveProductFromRepricer(null)) {
                return ['repricer' => null];
            }

            return [];
        }

        $strategy = $repricerTemplateSource->getStrategyName();
        $minPrice = $repricerTemplateSource->getRepricerMinPrice();
        $maxPrice = $repricerTemplateSource->getRepricerMaxPrice();

        if (!$this->isValidRepricerData($strategy, $minPrice, $maxPrice)) {
            if ($this->isNeedRemoveProductFromRepricer($strategy)) {
                return ['repricer' => null];
            }

            return [];
        }

        return [
            'repricer' => [
                'strategy_name' => $strategy,
                'min_price' => (float)$minPrice,
                'max_price' => (float)$maxPrice,
            ],
        ];
    }

    private function isNeedRemoveProductFromRepricer(?string $strategy): bool
    {
        if (empty($this->getWalmartListingProduct()->getOnlineRepricerStrategyName())) {
            return false;
        }

        if (empty($strategy)) {
            return true;
        }

        return false;
    }

    private function isValidRepricerData(?string $strategy, ?float $minPrice, ?float $maxPrice): bool
    {
        if ($strategy === null) {
            return false;
        }

        if (empty($minPrice)) {
            return false;
        }

        if (empty($maxPrice)) {
            return false;
        }

        return true;
    }
}
