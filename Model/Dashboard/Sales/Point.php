<?php

namespace Ess\M2ePro\Model\Dashboard\Sales;

class Point
{
    /** @var float */
    private $value;
    /** @var \DateTime $date */
    private $date;

    public function __construct(float $value, \DateTime $date)
    {
        $this->value = $value;
        $this->date = $date;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }
}
