<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetCategories\Response;

class Part
{
    private int $totalParts;
    private ?int $nextPartNumber;

    public function __construct(int $totalParts, ?int $nextPartNumber)
    {
        $this->totalParts = $totalParts;
        $this->nextPartNumber = $nextPartNumber;
    }

    public function getTotalParts(): int
    {
        return $this->totalParts;
    }

    public function getNextPartNumber(): ?int
    {
        return $this->nextPartNumber;
    }
}
