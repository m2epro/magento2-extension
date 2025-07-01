<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto;

class AddCampaignItem
{
    private string $id;
    private float $rate;

    public function __construct(string $id, float $rate)
    {
        $this->id = $id;
        $this->rate = $rate;
    }
}
