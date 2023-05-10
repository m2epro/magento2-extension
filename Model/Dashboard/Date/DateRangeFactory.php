<?php

namespace Ess\M2ePro\Model\Dashboard\Date;

class DateRangeFactory
{
    public function createForLast7Days(): DateRange
    {
        $dateEnd = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateStart = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateEnd->setTime(23, 59, 59);
        $dateStart->setTime(0, 0);
        $dateStart->modify('-6 days');

        return $this->create($dateStart, $dateEnd);
    }

    public function createForToday(): DateRange
    {
        $dateEnd = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateStart = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateEnd->setTime(23, 59, 59);
        $dateStart->setTime(0, 0);

        return $this->create($dateStart, $dateEnd);
    }

    public function createForYesterday(): DateRange
    {
        $dateEnd = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateStart = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateEnd->setTime(23, 59, 59);
        $dateStart->setTime(0, 0);
        $dateEnd->modify('-1 day');
        $dateStart->modify('-1 day');

        return $this->create($dateStart, $dateEnd);
    }

    public function createFor2DaysAgo(): DateRange
    {
        $dateEnd = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateStart = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateEnd->setTime(23, 59, 59);
        $dateStart->setTime(0, 0);
        $dateEnd->modify('-2 days');
        $dateStart->modify('-2 days');

        return $this->create($dateStart, $dateEnd);
    }

    public function createForLast24Hours(): DateRange
    {
        $dateEnd = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateStart = clone $dateEnd;
        $dateStart->modify('-1 day');

        return $this->create($dateStart, $dateEnd);
    }

    private function create(\DateTime $dateStart, \DateTime $dateEnd): DateRange
    {
        $gmt = new \DateTimeZone(\Ess\M2ePro\Helper\Date::getTimezone()->getDefaultTimezone());

        $dateEnd->setTimezone($gmt);
        $dateStart->setTimezone($gmt);

        return new DateRange($dateStart, $dateEnd);
    }
}
