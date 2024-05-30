<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product;

use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Promotion as EbayListingProductPromotionResource;

class Promotion extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(EbayListingProductPromotionResource::class);
    }

    public function init(
        int $accountId,
        int $marketplaceId,
        int $listingProductId,
        int $promotionId,
        ?int $discountId
    ): self {
        $this
            ->setAccountId($accountId)
            ->setMarketplaceId($marketplaceId)
            ->setListingProductId($listingProductId)
            ->setPromotionId($promotionId)
            ->setDiscountId($discountId);

        return $this;
    }

    // ----------------------------------------

    public function getAccountId(): int
    {
        return $this->getDataByKey(EbayListingProductPromotionResource::COLUMN_ACCOUNT_ID);
    }

    public function setAccountId(int $accountId): self
    {
        $this->setData(EbayListingProductPromotionResource::COLUMN_ACCOUNT_ID, $accountId);

        return $this;
    }

    // ----------------------------------------

    public function getMarketplaceId(): int
    {
        return $this->getDataByKey(EbayListingProductPromotionResource::COLUMN_MARKETPLACE_ID);
    }

    public function setMarketplaceId(int $marketplaceId): self
    {
        $this->setData(EbayListingProductPromotionResource::COLUMN_MARKETPLACE_ID, $marketplaceId);

        return $this;
    }

    // ----------------------------------------

    public function getListingProductId(): int
    {
        return (int)$this->getDataByKey(EbayListingProductPromotionResource::COLUMN_LISTING_PRODUCT_ID);
    }

    public function setListingProductId(int $listingProductId): self
    {
        $this->setData(EbayListingProductPromotionResource::COLUMN_LISTING_PRODUCT_ID, $listingProductId);

        return $this;
    }

    // ----------------------------------------

    public function getPromotionId(): int
    {
        return (int)$this->getDataByKey(EbayListingProductPromotionResource::COLUMN_PROMOTION_ID);
    }

    public function setPromotionId(int $promotionId): self
    {
        $this->setData(EbayListingProductPromotionResource::COLUMN_PROMOTION_ID, $promotionId);

        return $this;
    }

    // ----------------------------------------

    public function getDiscountId(): ?int
    {
        return $this->getDataByKey(EbayListingProductPromotionResource::COLUMN_DISCOUNT_ID);
    }

    public function setDiscountId(?int $discountId): self
    {
        $this->setData(EbayListingProductPromotionResource::COLUMN_DISCOUNT_ID, $discountId);

        return $this;
    }
}
