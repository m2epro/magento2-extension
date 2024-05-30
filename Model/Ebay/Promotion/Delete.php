<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class Delete
{
    /** @var \Ess\M2ePro\Model\Ebay\Promotion\Repository */
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function process(\Ess\M2ePro\Model\Ebay\Promotion $promotion): void
    {
        $this->removeListingProductPromotions($promotion);
        $this->repository->remove($promotion);
    }

    private function removeListingProductPromotions(\Ess\M2ePro\Model\Ebay\Promotion $promotion): void
    {
        $listingProductPromotions = $this->repository->findListingProductsByAccountAndMarketplaceAndPromotion(
            $promotion->getAccountId(),
            $promotion->getMarketplaceId(),
            $promotion->getId(),
        );

        foreach ($listingProductPromotions as $listingProductPromotion) {
            $this->repository->removeListingProductPromotion($listingProductPromotion);
        }
    }
}
