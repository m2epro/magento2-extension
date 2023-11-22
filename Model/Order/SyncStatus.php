<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Order;

class SyncStatus
{
    /** @var bool */
    private $isSuccess;
    /** @var \DateTime */
    private $lastRunDate;
    /** @var \DateTime|null */
    private $firstErrorDate;

    public function __construct(bool $isSuccess, \DateTime $lastRunDate, ?\DateTime $firstErrorDate)
    {
        $this->isSuccess = $isSuccess;
        $this->lastRunDate = $lastRunDate;
        $this->firstErrorDate = $firstErrorDate;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function getLastRunDate(): \DateTime
    {
        return $this->lastRunDate;
    }

    public function getFirstErrorDate(): ?\DateTime
    {
        return $this->firstErrorDate;
    }
}
