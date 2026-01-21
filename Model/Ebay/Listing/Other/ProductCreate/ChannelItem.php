<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate;

class ChannelItem
{
    public int $attributeSetId;
    public string $magentoProductType;
    public int $storeId;
    public int $taxClassId;
    public int $visibility;
    public string $title;
    public string $sku;
    public int $quantity;
    public float $price;
    public string $currencyCode;
    public int $stockStatus;
    public array $variationSet;
    public array $variations;
    public array $specifics;
    public string $description;
    public array $images;

    /**
     * @param ChannelAttributeItem[] $variationSet
     * @param ChannelItem[] $variations
     * @param array<string, string> $specifics
     * @param string[] $images
     */
    public function __construct(
        int $attributeSetId,
        string $magentoProductType,
        int $storeId,
        int $taxClassId,
        int $visibility,
        string $title,
        string $sku,
        int $quantity,
        float $price,
        string $currencyCode,
        int $stockStatus,
        string $description = '',
        array $variationSet = [],
        array $variations = [],
        array $specifics = [],
        array $images = []
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
        $this->variationSet = $variationSet;
        $this->variations = $variations;
        $this->specifics = $specifics;
        $this->images = $images;
    }

    public function isConfigurableProduct(): bool
    {
        return !empty($this->variations);
    }
}
