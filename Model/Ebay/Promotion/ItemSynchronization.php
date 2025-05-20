<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class ItemSynchronization
{
    private \Ess\M2ePro\Model\Listing\ProductFactory $listingProductFactory;
    private \Ess\M2ePro\Model\Ebay\Promotion\Repository $repository;
    private \Ess\M2ePro\Model\Listing\Log\Factory $logFactory;
    private \Ess\M2ePro\Model\Ebay\Listing\Product\PromotionFactory $listingProductPromotionFactory;
    private \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $ebayListingProductRepository;

    public function __construct(
        \Ess\M2ePro\Model\Listing\ProductFactory $listingProductFactory,
        \Ess\M2ePro\Model\Ebay\Promotion\Repository $repository,
        \Ess\M2ePro\Model\Listing\Log\Factory $logFactory,
        \Ess\M2ePro\Model\Ebay\Listing\Product\PromotionFactory $listingProductPromotionFactory,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $ebayListingProductRepository
    ) {
        $this->listingProductFactory = $listingProductFactory;
        $this->repository = $repository;
        $this->logFactory = $logFactory;
        $this->listingProductPromotionFactory = $listingProductPromotionFactory;
        $this->ebayListingProductRepository = $ebayListingProductRepository;
    }

    public function syncItems(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        \Ess\M2ePro\Model\Ebay\Promotion\Channel\Promotion $channelPromotion
    ): void {
        $channelItems = $channelPromotion->getItems();
        $existItems = $this->getItemsIds($promotion);

        $itemsToAdd = $this->getItemsToAdd($channelItems, $existItems);
        $listingProductPromotionToRemove = $this->getListingProductPromotionToRemove($channelItems, $existItems);

        $this->addItems($promotion, $itemsToAdd);
        $this->removeItems($promotion, $listingProductPromotionToRemove);
    }

    public function createItems(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        array $channelItems
    ): void {
        $this->addItems($promotion, $channelItems);
    }

    private function getItemsIds(\Ess\M2ePro\Model\Ebay\Promotion $promotion): array
    {
        $listingProductPromotions = $this->repository->findListingProductsByAccountAndMarketplaceAndPromotion(
            $promotion->getAccountId(),
            $promotion->getMarketplaceId(),
            $promotion->getId()
        );

        $items = [];

        foreach ($listingProductPromotions as $listingProductPromotion) {
            $listingProduct = $this->loadListingProduct($listingProductPromotion->getListingProductId());
            $itemId = $listingProduct->getChildObject()->getEbayItem()->getItemId();

            if (empty($itemId)) {
                continue;
            }

            $items[$itemId] = $listingProductPromotion;
        }

        return $items;
    }

    private function loadListingProduct(int $listingProductId): \Ess\M2ePro\Model\Listing\Product
    {
        $listingProduct = $this->listingProductFactory->create();
        $listingProduct->load($listingProductId);

        return $listingProduct;
    }

    // ----------------------------------------

    private function getItemsToAdd(array $channelItems, array $existItems): array
    {
        $itemsToAdd = [];
        foreach ($channelItems as $itemId) {
            if (!array_key_exists($itemId, $existItems)) {
                $itemsToAdd[] = $itemId;
            }
        }

        return $itemsToAdd;
    }

    private function getListingProductPromotionToRemove(array $channelItems, array $existItems): array
    {
        $listingProductPromotionToRemove = [];
        foreach ($existItems as $itemId => $listingProductPromotion) {
            if (!in_array((string)$itemId, $channelItems, true)) {
                $listingProductPromotionToRemove[] = $listingProductPromotion;
            }
        }

        return $listingProductPromotionToRemove;
    }

    // ----------------------------------------

    private function addItems(\Ess\M2ePro\Model\Ebay\Promotion $promotion, array $itemsIds): void
    {
        foreach ($itemsIds as $itemId) {
            $ebayListingProduct = $this->ebayListingProductRepository->findByItemId($itemId);

            if ($ebayListingProduct === null) {
                continue;
            }

            $listingProduct = $ebayListingProduct->getParentObject();

            $this->createListingProductPromotion($promotion, $listingProduct);

            $message = (string)__(
                'Item was added to Discount "%discount_name"',
                ['discount_name' => $promotion->getName()]
            );
            $this->writeLog($listingProduct, $message);
        }
    }

    private function createListingProductPromotion(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        \Ess\M2ePro\Model\Listing\Product $listingProduct
    ): void {
        $listingProductPromotion = $this->listingProductPromotionFactory->create();
        $listingProductPromotion->init(
            $promotion->getAccountId(),
            $promotion->getMarketplaceId(),
            (int)$listingProduct->getId(),
            $promotion->getId(),
            null
        );

        $this->repository->createListingProductPromotion($listingProductPromotion);
    }

    // ----------------------------------------

    private function removeItems(\Ess\M2ePro\Model\Ebay\Promotion $promotion, array $listingProductPromotions): void
    {
        foreach ($listingProductPromotions as $listingProductPromotion) {
            $this->removeListingProductPromotion($promotion, $listingProductPromotion);
        }
    }

    private function removeListingProductPromotion(
        \Ess\M2ePro\Model\Ebay\Promotion $promotion,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Promotion $listingProductPromotion
    ): void {
        $this->repository->removeListingProductPromotion($listingProductPromotion);

        $listingProduct = $this->loadListingProduct($listingProductPromotion->getListingProductId());

        $message = (string)__(
            'Item was removed from Discount "%discount_name"',
            ['discount_name' => $promotion->getName()]
        );
        $this->writeLog($listingProduct, $message);
    }

    private function writeLog(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        string $message
    ): void {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Log $log */
        $log = $this->logFactory->create();
        $log->setComponentMode($listingProduct->getComponentMode());

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_PROMOTION,
            $message,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
        );
    }
}
