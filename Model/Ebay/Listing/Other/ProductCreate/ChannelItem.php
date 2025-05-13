<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate;

class ChannelItem
{
    private int $attributeSetId;
    private string $magentoProductType;
    private int $storeId;
    private int $taxClassId;
    private int $visibility;
    private string $title;
    private string $description;
    private string $sku;
    private int $quantity;
    private float $price;
    private string $currencyCode;
    private int $stockStatus;
    /** @var ChannelAttributeItem[] */
    private array $variationSet = [];
    /** @var ChannelItem[] */
    private array $variations = [];
    private array $specifics = [];

    public function __construct(
        int $attributeSetId,
        string $magentoProductType,
        int $storeId,
        int $taxClassId,
        int $visibility,
        string $title,
        string $description,
        string $sku,
        int $quantity,
        float $price,
        string $currencyCode,
        int $stockStatus
    ) {
        $this->attributeSetId = $attributeSetId;
        $this->magentoProductType = $magentoProductType;
        $this->storeId = $storeId;
        $this->taxClassId = $taxClassId;
        $this->visibility = $visibility;
        $this->title = $title;
        $this->description = $description;
        $this->sku = $sku;
        $this->quantity = $quantity;
        $this->price = $price;
        $this->currencyCode = $currencyCode;
        $this->stockStatus = $stockStatus;
    }

    public function isConfigurableProduct(): bool
    {
        return !empty($this->getVariations());
    }

    public function getAttributeSetId(): int
    {
        return $this->attributeSetId;
    }

    public function getMagentoProductType(): string
    {
        return $this->magentoProductType;
    }

    public function getStoreId(): int
    {
        return $this->storeId;
    }

    public function getTaxClassId(): int
    {
        return $this->taxClassId;
    }

    public function getVisibility(): int
    {
        return $this->visibility;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getStockStatus(): int
    {
        return $this->stockStatus;
    }

    public function getSpecifics(): array
    {
        return $this->specifics;
    }

    /**
     * @return ChannelAttributeItem[]
     */
    public function getVariationSet(): array
    {
        return $this->variationSet;
    }

    /**
     * @return ChannelItem[]
     */
    public function getVariations(): array
    {
        return $this->variations;
    }

    public function setSpecifics(array $specifics): void
    {
        $this->specifics = $specifics;
    }

    public function setVariations(array $variations): void
    {
        $this->variations = $variations;
    }

    public function setVariationSet(array $variationSet): void
    {
        $this->variationSet = $variationSet;
    }
}
