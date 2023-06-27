<?php

namespace Ess\M2ePro\Model\Dashboard\ListingProductIssues;

class Issue
{
    /** @var int */
    private $tagId;
    /** @var string */
    private $text;
    /** @var int */
    private $total;
    /** @var float */
    private $impactRate;

    public function __construct(
        int $tagId,
        string $text,
        int $total,
        float $impactRate
    ) {
        $this->tagId = $tagId;
        $this->text = $text;
        $this->total = $total;
        $this->impactRate = $impactRate;
    }

    public function getTagId(): int
    {
        return $this->tagId;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getImpactRate(): float
    {
        return $this->impactRate;
    }
}
