<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto;

class Campaign
{
    private string $id;
    private string $name;
    private string $status;
    private string $type;
    private \DateTime $startDate;
    private ?\DateTime $endDate;
    private int $marketplaceId;
    private float $rate;

    public function __construct(
        string $id,
        string $name,
        string $status,
        string $type,
        \DateTime $startDate,
        ?\DateTime $endDate,
        int $marketplaceId,
        float $rate
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->status = $status;
        $this->type = $type;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->marketplaceId = $marketplaceId;
        $this->rate = $rate;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getType(): string
    {
        return $this->type;
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

    public function getMarketplaceId(): int
    {
        return $this->marketplaceId;
    }

    public function getRate(): float
    {
        return $this->rate;
    }
}
