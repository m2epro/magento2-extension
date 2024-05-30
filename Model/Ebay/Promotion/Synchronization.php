<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class Synchronization
{
    private \Ess\M2ePro\Model\Ebay\Promotion\Repository $repository;
    private \Ess\M2ePro\Model\Ebay\Promotion\Channel\Retrieve $retrieverFromChannel;
    private \Ess\M2ePro\Model\Ebay\Promotion\Delete $deleteService;
    private \Ess\M2ePro\Model\Ebay\Promotion\UpdateFromChannel $updateFromChannelService;
    private \Ess\M2ePro\Model\Ebay\Promotion\Create $createService;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Promotion\Repository $repository,
        \Ess\M2ePro\Model\Ebay\Promotion\Channel\Retrieve $retrieverFromChannel,
        \Ess\M2ePro\Model\Ebay\Promotion\Delete $deleteService,
        \Ess\M2ePro\Model\Ebay\Promotion\UpdateFromChannel $updateFromChannelService,
        \Ess\M2ePro\Model\Ebay\Promotion\Create $createService
    ) {
        $this->repository = $repository;
        $this->retrieverFromChannel = $retrieverFromChannel;
        $this->deleteService = $deleteService;
        $this->updateFromChannelService = $updateFromChannelService;
        $this->createService = $createService;
    }

    public function process(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        \Ess\M2ePro\Model\Marketplace $marketplace
    ): void {
        $channelPromotionsCollection = $this->retrieverFromChannel->getChannelPromotions($ebayAccount, $marketplace);

        $existPromotionsCollection = $this->repository->findByAccountAndMarketplace(
            (int)$ebayAccount->getId(),
            (int)$marketplace->getId()
        );

        $this->remove($channelPromotionsCollection, $existPromotionsCollection);
        $this->update($channelPromotionsCollection, $existPromotionsCollection);
        $this->create($channelPromotionsCollection, $existPromotionsCollection, $ebayAccount, $marketplace);
    }

    private function remove(
        Channel\PromotionCollection $channelPromotionsCollection,
        Collection $existPromotionsCollection
    ): void {
        foreach ($existPromotionsCollection->getAll() as $existPromotion) {
            if ($channelPromotionsCollection->has($existPromotion->getPromotionId())) {
                continue;
            }

            $this->deleteService->process($existPromotion);

            $existPromotionsCollection->remove($existPromotion);
        }
    }

    private function update(
        Channel\PromotionCollection $channelPromotionsCollection,
        Collection $existPromotionsCollection
    ): void {
        foreach ($existPromotionsCollection->getAll() as $existPromotion) {
            if (!$channelPromotionsCollection->has($existPromotion->getPromotionId())) {
                continue;
            }

            $this->updateFromChannelService->process(
                $existPromotion,
                $channelPromotionsCollection->get($existPromotion->getPromotionId()),
            );

            $channelPromotionsCollection->remove($existPromotion->getPromotionId());
        }
    }

    private function create(
        Channel\PromotionCollection $channelPromotionsCollection,
        Collection $existPromotionsCollection,
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        \Ess\M2ePro\Model\Marketplace $marketplace
    ): void {
        foreach ($channelPromotionsCollection->getAll() as $channelPromotion) {
            if ($existPromotionsCollection->hasByPromotionId($channelPromotion->getPromotionId())) {
                continue;
            }

            $promotion = $this->createService->process($ebayAccount, $marketplace, $channelPromotion);

            $existPromotionsCollection->add($promotion);
        }
    }
}
