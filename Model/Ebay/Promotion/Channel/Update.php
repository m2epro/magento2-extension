<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion\Channel;

class Update
{
    private \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher;
    private \Ess\M2ePro\Model\AccountFactory $accountFactory;
    private \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher,
        \Ess\M2ePro\Model\AccountFactory $accountFactory,
        \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory
    ) {
        $this->dispatcher = $dispatcher;
        $this->accountFactory = $accountFactory;
        $this->marketplaceFactory = $marketplaceFactory;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Promotion $promotion
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function updateChannelPromotion(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        array $listingProducts
    ): array {
        $channelPromotion = $this->createChannelPromotion($promotion, $listingProducts);

        /** @var \Ess\M2ePro\Model\Ebay\Connector\Promotion\Update\ItemConnector $connectorObj */
        $connectorObj = $this->dispatcher->getConnector(
            'Promotion',
            'Update',
            'ItemConnector',
            [
                'account' => $this->getAccountServerHash($promotion),
                'marketplace' => $this->getMarketplaceNativeId($promotion),
                'promotion_id' => $channelPromotion->getPromotionId(),
                'items_ids' => $channelPromotion->getItems(),
            ]
        );

        $this->dispatcher->process($connectorObj);

        return $connectorObj->getResponseData();
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Promotion $promotion
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     *
     * @return \Ess\M2ePro\Model\Ebay\Promotion\Channel\Promotion
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function createChannelPromotion(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        array $listingProducts
    ): \Ess\M2ePro\Model\Ebay\Promotion\Channel\Promotion {
        return new \Ess\M2ePro\Model\Ebay\Promotion\Channel\Promotion(
            $promotion->getPromotionId(),
            $promotion->getName(),
            $promotion->getType(),
            $promotion->getStatus(),
            $promotion->getPriority(),
            $promotion->getStartDate(),
            $promotion->getEndDate(),
            [], //$discounts
            $this->getChannelItemsIds($listingProducts)
        );
    }

    private function getAccountServerHash(\Ess\M2ePro\Model\Ebay\Promotion $promotion): string
    {
        $accountId = $promotion->getAccountId();
        $account = $this->accountFactory->create()->load($accountId);

        return $account->getChildObject()->getServerHash();
    }

    private function getMarketplaceNativeId(\Ess\M2ePro\Model\Ebay\Promotion $promotion): int
    {
        $marketplaceId = $promotion->getMarketplaceId();
        $marketplace = $this->marketplaceFactory->create()->load($marketplaceId);

        return $marketplace->getNativeId();
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getChannelItemsIds(array $listingProducts): array
    {
        $itemsIds = [];

        foreach ($listingProducts as $listingProduct) {
            $itemId = $listingProduct->getChildObject()->getEbayItem()->getItemId();

            if (empty($itemId)) {
                continue;
            }

            $itemsIds[] = $itemId;
        }

        return $itemsIds;
    }
}
