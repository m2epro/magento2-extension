<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker;

class TrackerConfiguration
{
    public string $channel;
    public int $listingProductIdFrom;
    public int $listingProductIdTo;

    public function __construct(string $channel, int $listingProductIdFrom, int $listingProductIdTo)
    {
        $this->channel = $channel;
        $this->listingProductIdFrom = $listingProductIdFrom;
        $this->listingProductIdTo = $listingProductIdTo;
    }
}
