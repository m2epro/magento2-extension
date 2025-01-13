<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class DeletePromotionProductsWithoutPromotion
{
    /** @var \Ess\M2ePro\Model\Ebay\Promotion\Repository */
    private Repository $promotionRepository;

    public function __construct(Repository $promotionRepository)
    {
        $this->promotionRepository = $promotionRepository;
    }

    public function process()
    {
        $promotionProducts = $this->promotionRepository->findPromotionProductsWithoutPromotion();
        foreach ($promotionProducts as $promotionProduct) {
            $this->promotionRepository->removeListingProductPromotion($promotionProduct);
        }
    }
}
