<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class Create
{
    private Repository $repository;
    private \Ess\M2ePro\Model\Ebay\PromotionFactory $factory;
    private DiscountFactory $discountFactory;

    public function __construct(
        Repository $repository,
        \Ess\M2ePro\Model\Ebay\PromotionFactory $factory,
        \Ess\M2ePro\Model\Ebay\Promotion\DiscountFactory $discountFactory
    ) {
        $this->repository = $repository;
        $this->factory = $factory;
        $this->discountFactory = $discountFactory;
    }

    public function process(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        \Ess\M2ePro\Model\Marketplace $marketplace,
        \Ess\M2ePro\Model\Ebay\Promotion\Channel\Promotion $channelPromotion
    ): \Ess\M2ePro\Model\Ebay\Promotion {
        $promotion = $this->factory->create();
        $promotion->init(
            (int)$ebayAccount->getId(),
            (int)$marketplace->getId(),
            $channelPromotion->getPromotionId(),
            $channelPromotion->getName(),
            $channelPromotion->getType(),
            $channelPromotion->getStatus(),
            $channelPromotion->getPriority(),
            $channelPromotion->getStartDate(),
            $channelPromotion->getEndDate(),
        );

        $this->repository->create($promotion);

        if ($promotion->isTypeWithDiscounts()) {
            $this->createDiscounts($promotion, $channelPromotion->getDiscounts());
        }

        return $promotion;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Promotion $promotion
     * @param \Ess\M2ePro\Model\Ebay\Promotion\Channel\Discount[] $channelDiscounts
     */
    private function createDiscounts(\Ess\M2ePro\Model\Ebay\Promotion $promotion, array $channelDiscounts): void
    {
        foreach ($channelDiscounts as $channelDiscount) {
            /** @var \Ess\M2ePro\Model\Ebay\Promotion\Channel\Discount $channelDiscount */
            $discount = $this->discountFactory->create();
            $discount->init(
                $promotion->getId(),
                $channelDiscount->getDiscountId(),
                $channelDiscount->getTitle()
            );

            $this->repository->createDiscount($discount);
        }
    }
}
