<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class UpdateFromChannel
{
    private Repository $repository;
    private \Ess\M2ePro\Model\Ebay\Promotion\ItemSynchronization $itemSynchronizationService;

    public function __construct(
        Repository $repository,
        \Ess\M2ePro\Model\Ebay\Promotion\ItemSynchronization $itemSynchronizationService
    ) {
        $this->repository = $repository;
        $this->itemSynchronizationService = $itemSynchronizationService;
    }

    public function process(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        \Ess\M2ePro\Model\Ebay\Promotion\Channel\Promotion $channel
    ): void {
        if ($this->hasChanges($promotion, $channel)) {
            $promotion->setName($channel->getName())
                      ->setType($channel->getType())
                      ->setStatus($channel->getStatus())
                      ->setPriority($channel->getPriority())
                      ->setStartDate($channel->getStartDate())
                      ->setEndDate($channel->getEndDate());

            $this->repository->save($promotion);
        }

        $this->itemSynchronizationService->syncItems($promotion, $channel);

        /*
        if (!$promotion->isTypeWithDiscounts()) {
            return;
        }

        $existDiscounts = $this->repository->findDiscountsByPromotionId($promotion->getId());
        $channelDiscounts = $channel->getDiscounts();

        if (!$this->isDiscountsChanged($existDiscounts, $channelDiscounts)) {
            return;
        }

        $this->removeDiscountsAndListingProductPromotions($promotion);
        $this->createDiscounts($promotion, $channelDiscounts);
        */
    }

    private function hasChanges(\Ess\M2ePro\Model\Ebay\Promotion $promotion, Channel\Promotion $channel): bool
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
