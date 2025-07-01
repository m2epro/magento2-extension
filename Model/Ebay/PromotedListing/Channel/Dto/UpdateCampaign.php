<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto;

class UpdateCampaign
{
    private string $id;
    private string $name;
    private \DateTime $startDate;
    private ?\DateTime $endDate;

    public function __construct(
        string $id,
        string $name,
        \DateTime $startDate,
        ?\DateTime $endDate
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getId(): string
    {
        return $this->id;
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
}
