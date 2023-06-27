<?php

namespace Ess\M2ePro\Model\Dashboard\Date;

class DateRangeFactory
{
    public function createForLast7Days(): DateRange
    {
        $dateStart = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateStart->setTime(0, 0);
        $dateStart->modify('-6 days');

        $dateEnd = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateEnd->setTime(23, 59, 59);

        return $this->create($dateStart, $dateEnd);
    }

    public function createForToday(): DateRange
    {
        $dateStart = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateStart->setTime(0, 0);

        $dateEnd = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateEnd->setTime(23, 59, 59);

        return $this->create($dateStart, $dateEnd);
    }

    public function createForTomorrow(): DateRange
    {
        $dateStart = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateStart->setTime(0, 0);
        $dateStart->modify('+1 day');

        $dateEnd = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateEnd->setTime(23, 59, 59);
        $dateEnd->modify('+1 day');

        return $this->create($dateStart, $dateEnd);
    }

    public function createForTwoAndMoreDays(): DateRange
    {
        $dateStart = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateStart->setTime(0, 0);
        $dateStart->modify('+2 day');

        $dateEnd = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateEnd->setTime(23, 59, 59);
        $dateEnd->modify('+1 year');

        return $this->create($dateStart, $dateEnd);
    }

    public function createForYesterday(): DateRange
    {
        $dateStart = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateStart->setTime(0, 0);
        $dateStart->modify('-1 day');

        $dateEnd = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateEnd->setTime(23, 59, 59);
        $dateEnd->modify('-1 day');

        return $this->create($dateStart, $dateEnd);
    }

    public function createFor2DaysAgo(): DateRange
    {
        $dateStart = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateStart->setTime(0, 0);
        $dateStart->modify('-2 days');

        $dateEnd = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateEnd->setTime(23, 59, 59);
        $dateEnd->modify('-2 days');

        return $this->create($dateStart, $dateEnd);
    }

    public function createForLast24Hours(): DateRange
    {
        $dateStart = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();
        $dateStart->modify('-1 day');

        $dateEnd = \Ess\M2ePro\Helper\Date::createCurrentInCurrentZone();

        return $this->create($dateStart, $dateEnd);
    }

    private function create(\DateTime $dateStart, \DateTime $dateEnd): DateRange
    {
        $gmt = new \DateTimeZone(\Ess\M2ePro\Helper\Date::getTimezone()->getDefaultTimezone());

        $dateStart->setTimezone($gmt);
        $dateEnd->setTimezone($gmt);

        return new DateRange($dateStart, $dateEnd);
    }
}
