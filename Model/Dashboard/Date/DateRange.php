<?php

namespace Ess\M2ePro\Model\Dashboard\Date;

class DateRange
{
    /** @var \DateTime */
    private $dateStart;
    /** @var \DateTime */
    private $dateEnd;

    public function __construct(\DateTime $dateStart, \DateTime $dateEnd)
    {
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
    }

    public function getDateStart(): \DateTime
    {
        return $this->dateStart;
    }

    public function getDateEnd(): \DateTime
    {
        return $this->dateEnd;
    }
}
