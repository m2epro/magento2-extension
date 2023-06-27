<?php

namespace Ess\M2ePro\Model\Dashboard\Shipments;

interface CalculatorInterface
{
    public function getCountOfLateShipments(): int;

    public function getCountOfShipByToday(): int;

    public function getCountOfShipByTomorrow(): int;

    public function getCountForTwoAndMoreDays(): int;
}
