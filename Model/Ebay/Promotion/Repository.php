<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Promotion\CollectionFactory;
use Ess\M2ePro\Model\ResourceModel\Ebay\Promotion as EbayPromotionResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\Discount as EbayPromotionDiscountResource;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Promotion as EbayListingProductPromotionResource;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion $promotionResource;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\Discount $promotionDiscountResource;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Promotion $listingProductPromotionResource;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\CollectionFactory $promotionCollectionFactory;
    private EbayPromotionDiscountResource\CollectionFactory $promotionDiscountCollectionFactory;
    private CollectionFactory $listingProductPromotionCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion $promotionResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\CollectionFactory $promotionCollectionFactory,
        EbayPromotionDiscountResource\CollectionFactory $promotionDiscountCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\Discount $promotionDiscountResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Promotion $listingProductPromotionResource,
        CollectionFactory $listingProductPromotionCollectionFactory
    ) {
        $this->promotionResource = $promotionResource;
        $this->promotionDiscountResource = $promotionDiscountResource;
        $this->listingProductPromotionResource = $listingProductPromotionResource;
        $this->promotionCollectionFactory = $promotionCollectionFactory;
        $this->promotionDiscountCollectionFactory = $promotionDiscountCollectionFactory;
        $this->listingProductPromotionCollectionFactory = $listingProductPromotionCollectionFactory;
    }

    public function create(\Ess\M2ePro\Model\Ebay\Promotion $promotion): void
    {
        $promotion->isObjectCreatingState(true);
        $this->promotionResource->save($promotion);
    }

    public function save(\Ess\M2ePro\Model\Ebay\Promotion $promotion): void
    {
        $this->promotionResource->save($promotion);
    }

    public function remove(\Ess\M2ePro\Model\Ebay\Promotion $promotion): void
    {
        $this->promotionResource->delete($promotion);
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Promotion[]
     */
    public function getAll(): array
    {
        $collection = $this->promotionCollectionFactory->create();

        return array_values($collection->getItems());
    }

    public function findByAccountAndMarketplace(int $accountId, int $marketplaceId): Collection
    {
        $collection = $this->promotionCollectionFactory->create();
        $collection->addFieldToFilter(
            EbayPromotionResource::COLUMN_ACCOUNT_ID,
            $accountId,
        );
        $collection->addFieldToFilter(
            EbayPromotionResource::COLUMN_MARKETPLACE_ID,
            $marketplaceId,
        );

        $result = new Collection();
        foreach ($collection->getItems() as $promotion) {
            $result->add($promotion);
        }

        return $result;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Promotion[]
     */
    public function findExpiredPromotions(): array
    {
        $collection = $this->promotionCollectionFactory->create();
        $collection->addFieldToFilter(
            EbayPromotionResource::COLUMN_END_DATE,
            ['lt' => \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s')]
        );

        return array_values($collection->getItems());
    }

    public function createDiscount(\Ess\M2ePro\Model\Ebay\Promotion\Discount $discount): void
    {
        $discount->isObjectCreatingState(true);
        $this->promotionDiscountResource->save($discount);
    }

    public function saveDiscount(\Ess\M2ePro\Model\Ebay\Promotion\Discount $discount): void
    {
        $this->promotionDiscountResource->save($discount);
    }

    public function removeDiscount(\Ess\M2ePro\Model\Ebay\Promotion\Discount $discount): void
    {
        $this->promotionDiscountResource->delete($discount);
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Promotion\Discount[]
     */
    public function findDiscountsByPromotionId(int $promotionId): array
    {
        $collection = $this->promotionDiscountCollectionFactory->create();
        $collection->addFieldToFilter(
            EbayPromotionDiscountResource::COLUMN_PROMOTION_ID,
            $promotionId,
        );

        return array_values($collection->getItems());
    }

    // ----------------------------------------

    public function createListingProductPromotion(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Promotion $listingProductPromotion
    ): void {
        $listingProductPromotion->isObjectCreatingState(true);
        $this->listingProductPromotionResource->save($listingProductPromotion);
    }

    public function saveListingProductPromotion(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Promotion $listingProductPromotion
    ): void {
        $this->listingProductPromotionResource->save($listingProductPromotion);
    }

    public function removeListingProductPromotion(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Promotion $listingProductPromotion
    ): void {
        $this->listingProductPromotionResource->delete($listingProductPromotion);
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Promotion[]
     */
    public function findListingProductsByAccountAndMarketplaceAndPromotion(
        int $accountId,
        int $marketplaceId,
        int $promotionId
    ): array {
        $collection = $this->listingProductPromotionCollectionFactory->create();
        $collection->addFieldToFilter(
            EbayListingProductPromotionResource::COLUMN_ACCOUNT_ID,
            $accountId,
        );
        $collection->addFieldToFilter(
            EbayListingProductPromotionResource::COLUMN_MARKETPLACE_ID,
            $marketplaceId,
        );
        $collection->addFieldToFilter(
            EbayListingProductPromotionResource::COLUMN_PROMOTION_ID,
            $promotionId,
        );

        return array_values($collection->getItems());
    }

    public function removeAllByAccountId(int $accountId)
    {
        $promotionCollection = $this->promotionCollectionFactory->create();
        $promotionCollection->addFieldToFilter('account_id', $accountId);

        /*
        $promotionIds = $promotionCollection->getColumnValues('promotion_id');

        if (!empty($promotionIds)) {
            $discountCollection = $this->promotionDiscountCollectionFactory->create();
            $discountCollection->getConnection()->delete(
                $discountCollection->getMainTable(),
                ['promotion_id IN (?)' => $promotionIds]
            );
        }
        */

        $promotionCollection->getConnection()->delete(
            $promotionCollection->getMainTable(),
            ['account_id = ?' => $accountId],
        );

        $collectionListingProduct = $this->listingProductPromotionCollectionFactory->create();
        $collectionListingProduct->getConnection()->delete(
            $collectionListingProduct->getMainTable(),
            ['account_id = ?' => $accountId],
        );
    }

    public function removeListingProductPromotionByListingProductId(int $listingProductId)
    {
        $collectionListingProduct = $this->listingProductPromotionCollectionFactory->create();
        $collectionListingProduct->getConnection()->delete(
            $collectionListingProduct->getMainTable(),
            ['listing_product_id = ?' => $listingProductId],
        );
    }

    public function isProductInPromotion(int $listingProductId): bool
    {
        $collectionListingProduct = $this->listingProductPromotionCollectionFactory->create();
        $collectionListingProduct->addFieldToFilter(
            EbayListingProductPromotionResource::COLUMN_LISTING_PRODUCT_ID,
            $listingProductId
        );

        return (bool)$collectionListingProduct->getSize();
    }

    public function hasProductPromotionByAccountAndMarketplace(
        int $accountId,
        int $marketplaceId
    ): bool {
        $collectionListingProduct = $this->listingProductPromotionCollectionFactory->create();
        $collectionListingProduct->addFieldToFilter(
            EbayListingProductPromotionResource::COLUMN_ACCOUNT_ID,
            $accountId
        );
        $collectionListingProduct->addFieldToFilter(
            EbayListingProductPromotionResource::COLUMN_MARKETPLACE_ID,
            $marketplaceId
        );

        return (bool)$collectionListingProduct->getSize();
    }
}
