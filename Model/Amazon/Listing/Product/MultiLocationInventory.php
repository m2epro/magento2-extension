<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

class MultiLocationInventory
{
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\MultiLocationInventory\LocationInventory[] */
    private array $locations;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\MultiLocationInventory\LocationInventory[] $locations
     */
    public function __construct(array $locations)
    {
        $this->locations = $locations;
    }

    public function getTotalQuantity(): int
    {
        $sum = 0;
        foreach ($this->locations as $item) {
            $sum += $item->quantity;
        }

        return $sum;
    }

    public function toString(): string
    {
        $parts = [];
        foreach ($this->locations as $location) {
            $parts[] = "$location->amazonLocationTitle: $location->quantity";
        }

        return implode(', ', $parts);
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\MultiLocationInventory\LocationInventory[]
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    public function isEmpty(): bool
    {
        return count($this->locations) === 0;
    }
}
