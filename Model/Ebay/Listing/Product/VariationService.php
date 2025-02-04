<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product;

class VariationService
{
    private Variation\Updater $updater;

    public function __construct(Variation\Updater $updater)
    {
        $this->updater = $updater;
    }

    public function update(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $this->updater->process($listingProduct);
    }
}
