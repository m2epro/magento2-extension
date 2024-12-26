<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker;

class TrackerConfiguration
{
    public string $component;
    public int $listingProductIdFrom;
    public int $listingProductIdTo;

    public function __construct(string $component, int $listingProductIdFrom, int $listingProductIdTo)
    {
        $this->component = $component;
        $this->listingProductIdFrom = $listingProductIdFrom;
        $this->listingProductIdTo = $listingProductIdTo;
    }
}
