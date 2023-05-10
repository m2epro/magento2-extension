<?php

namespace Ess\M2ePro\Model\Dashboard\Errors;

interface CalculatorInterface
{
    public function getCountForToday(): int;

    public function getCountForYesterday(): int;

    public function getCountFor2DaysAgo(): int;

    public function getTotalCount(): int;
}
