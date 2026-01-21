<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader;

class ChannelItemInfo
{
    public string $description;
    public array $pictureUrls;
    public array $specifics;
    public array $variations;

    /**
     * @param string[] $pictureUrls
     * @param \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader\Specific[] $specifics
     * @param \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader\Variation[] $variations
     */
    public function __construct(
        string $description,
        array $pictureUrls,
        array $specifics,
        array $variations
    ) {
        $this->description = $description;
        $this->pictureUrls = $pictureUrls;
        $this->specifics = $specifics;
        $this->variations = $variations;
    }
}
