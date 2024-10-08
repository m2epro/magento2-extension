<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\ProductType\Service;

class ResultOfMassDelete
{
    private int $countDeleted = 0;
    private int $countLocked = 0;

    public function incrementCountDeleted(): void
    {
        $this->countDeleted++;
    }

    public function incrementCountLocked(): void
    {
        $this->countLocked++;
    }

    public function getCountDeleted(): int
    {
        return $this->countDeleted;
    }

    public function getCountLocked(): int
    {
        return $this->countLocked;
    }
}
