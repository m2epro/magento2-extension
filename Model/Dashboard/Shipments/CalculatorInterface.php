<?php

namespace Ess\M2ePro\Model\Dashboard\Shipments;

interface CalculatorInterface
{
    public function getCountOfLateShipments(): int;

    public function getCountByOver2Days(): int;

    public function getCountForToday(): int;

    public function getTotalCount(): int;
}
