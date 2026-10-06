<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Order;

class CanCreateResult
{
    /** @var \Ess\M2ePro\Model\Order\CanCreateResultMessage[] */
    private array $reasons = [];

    public function addReason(CanCreateResultMessage $message)
    {
        $this->reasons[] = $message;
    }

    /**
     * @return \Ess\M2ePro\Model\Order\CanCreateResultMessage[]
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }
}
