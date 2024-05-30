<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class UpdateFromChannel
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function process(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        \Ess\M2ePro\Model\Ebay\Promotion\Channel\Promotion $channel
    ): void {
        if (!$this->nothingUpdate($promotion, $channel)) {
            return;
        }

        $promotion->setName($channel->getName())
                       ->setType($channel->getType())
                       ->setStatus($channel->getStatus())
                       ->setPriority($channel->getPriority())
                       ->setStartDate($channel->getStartDate())
                       ->setEndDate($channel->getEndDate());

        $this->repository->save($promotion);

        if (!$promotion->isTypeWithDiscounts()) {
            return;
        }

        $existDiscounts = $this->repository->findDiscountsByPromotionId($promotion->getId());
        $channelDiscounts = $channel->getDiscounts();

        // todo
        if (!$this->isDiscountsChanged($existDiscounts, $channelDiscounts)) {
            return;
        }

        //$this->removeDiscountsAndListingProductPromotions($promotion);
        //$this->createDiscounts($promotion, $channelDiscounts);
    }

    private function nothingUpdate(\Ess\M2ePro\Model\Ebay\Promotion $promotion, Channel\Promotion $channel): bool
    {
        // todo check discounts if exist
        return $promotion->getName() !== $channel->getName()
            || $promotion->getType() !== $channel->getType()
            || $promotion->getStatus() !== $channel->getStatus()
            || $promotion->getPriority() !== $channel->getPriority()
            || $promotion->getStartDate() != $channel->getStartDate() // == !!
            || $promotion->getEndDate() != $channel->getEndDate();
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Promotion\Discount[] $existDiscounts
     * @param \Ess\M2ePro\Model\Ebay\Promotion\Channel\Discount[] $channelDiscounts
     */
    private function isDiscountsChanged(
        array $existDiscounts,
        array $channelDiscounts
    ): bool {
        $existDiscountIds = [];
        foreach ($existDiscounts as $existDiscount) {
            $existDiscountIds[] = $existDiscount->getDiscountId();
        }

        $channelDiscountIds = [];
        foreach ($channelDiscounts as $channelDiscount) {
            $channelDiscountIds[] = $channelDiscount->getDiscountId();
        }

        sort($existDiscountIds);
        sort($channelDiscountIds);

        return $existDiscountIds !== $channelDiscountIds;
    }
}
