<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion\Channel;

class Promotion
{
    private string $promotionId;
    private string $name;
    private string $type;
    private string $status;
    private string $priority;
    private ?\DateTimeInterface $startDate;
    private ?\DateTimeInterface $endDate;
    private array $discounts;
    private array $items;

    public function __construct(
        string $promotionId,
        string $name,
        string $type,
        string $status,
        string $priority,
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        array $discounts = [],
        array $items = []
    ) {
        $this->promotionId = $promotionId;
        $this->name = $name;
        $this->type = $type;
        $this->status = $status;
        $this->priority = $priority;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->discounts = $discounts;
        $this->items = $items;
    }

    public function getPromotionId(): string
    {
        return $this->promotionId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function getDiscounts(): array
    {
        return $this->discounts;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
