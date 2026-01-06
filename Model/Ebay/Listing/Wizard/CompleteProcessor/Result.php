<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard\CompleteProcessor;

class Result
{
    private array $addedListingProducts;
    private array $notAddedProducts;

    public function __construct(array $addedListingProducts, array $notAddedProducts)
    {
        $this->addedListingProducts = $addedListingProducts;
        $this->notAddedProducts = $notAddedProducts;
    }

    public function getAddedListingProducts(): array
    {
        return $this->addedListingProducts;
    }

    public function hasNotAddedProducts(): bool
    {
        return count($this->notAddedProducts) > 0;
    }
}
