<?php

namespace Ess\M2ePro\Model\Dashboard\Sales;

class PointFactory
{
    public function createSet(): PointSet
    {
        return new PointSet();
    }

    public function createPoint(float $value, string $dateTimeStr): Point
    {
        $dateTime = new \DateTime(
            $dateTimeStr,
            new \DateTimeZone(\Ess\M2ePro\Helper\Date::getTimezone()->getDefaultTimezone())
        );

        return new Point($value, $dateTime);
    }
}
