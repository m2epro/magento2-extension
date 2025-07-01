<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto;

class CreateCampaign
{
    private string $name;
    private \DateTime $startDate;
    private ?\DateTime $endDate;
    private float $rate;

    public function __construct(
        string $name,
        \DateTime $startDate,
        ?\DateTime $endDate,
        float $rate
    ) {
        $this->name = $name;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->rate = $rate;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFormattedStartDate(): string
    {
        return $this->startDate->format('Y-m-d H:i:s');
    }

    public function getFormattedEndDate(): ?string
    {
        if ($this->endDate === null) {
            return null;
        }

        return $this->endDate->format('Y-m-d H:i:s');
    }

    public function getRate(): float
    {
        return $this->rate;
    }
}
