<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader;

class Variation
{
    public string $sku;
    public array $images;
    public array $specifics;

    /**
     * @param string[] $images Images URLs
     * @param array<string, string> $specifics Key value array. Key - specific name, value - specific value
     */
    public function __construct(
        string $sku,
        array $images,
        array $specifics
    ) {
        $this->sku = $sku;
        $this->images = $images;
        $this->specifics = $specifics;
    }
}
