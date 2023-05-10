<?php

namespace Ess\M2ePro\Model\Dashboard\Sales;

class PointSet
{
    /** @var Point[]  */
    private $points = [];

    public function addPoint(Point $point)
    {
        $this->points[] = $point;
    }

    /**
     * @return Point[]
     */
    public function getPoints(): array
    {
        return $this->points;
    }
}
