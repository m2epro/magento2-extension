<?php

namespace Ess\M2ePro\Model\Dashboard\ListingProductIssues\Repository;

class Record
{
    /** @var int */
    private $total;
    /** @var int */
    private $tagId;
    /** @var float */
    private $impactRate;
    /** @var string */
    private $text;

    public function __construct(int $total, int $tagId, float $impactRate, string $text)
    {
        $this->total = $total;
        $this->tagId = $tagId;
        $this->impactRate = $impactRate;
        $this->text = $text;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getTagId(): int
    {
        return $this->tagId;
    }

    public function getImpactRate(): float
    {
        return $this->impactRate;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
