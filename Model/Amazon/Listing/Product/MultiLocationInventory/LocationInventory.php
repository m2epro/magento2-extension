<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product\MultiLocationInventory;

class LocationInventory
{
    public string $amazonLocationCode;
    public string $amazonLocationTitle;
    public int $quantity;

    public function __construct(
        string $amazonLocationCode,
        string $amazonLocationTitle,
        int $quantity
    ) {
        $this->amazonLocationCode = $amazonLocationCode;
        $this->amazonLocationTitle = $amazonLocationTitle;
        $this->quantity = $quantity;
    }
}
