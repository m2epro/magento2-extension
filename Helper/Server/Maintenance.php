<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Server;

class Maintenance
{
    /** @var false | \DateTime | null */
    private $enabledFrom;
    /** @var false | \DateTime | null */
    private $enabledTo;

    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;

    /**
     * @param \Ess\M2ePro\Model\Registry\Manager $registry
     */
    public function __construct(
        \Ess\M2ePro\Model\Registry\Manager $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isScheduled(): bool
    {
        $dateEnabledFrom = $this->getDateEnabledFrom();
        $dateEnabledTo = $this->getDateEnabledTo();

        if ($dateEnabledFrom === false || $dateEnabledTo === false) {
            return false;
        }

        $dateCurrent = new \DateTime('now', new \DateTimeZone('UTC'));

        return $dateCurrent < $dateEnabledFrom && $dateCurrent < $dateEnabledTo;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isNow(): bool
    {
        $dateEnabledFrom = $this->getDateEnabledFrom();
        $dateEnabledTo = $this->getDateEnabledTo();

        if ($dateEnabledFrom === false || $dateEnabledTo === false) {
            return false;
        }

        $dateCurrent = new \DateTime('now', new \DateTimeZone('UTC'));

        return $dateCurrent > $dateEnabledFrom && $dateCurrent < $dateEnabledTo;
    }

    /**
     * @return \DateTime|false
     * @throws \Exception
     */
    public function getDateEnabledFrom()
    {
        if ($this->enabledFrom === null) {
            $dateEnabledFrom = $this->registry->getValue(
                '/server/maintenance/schedule/date/enabled/from/'
            );
            $this->enabledFrom = $dateEnabledFrom
                ? new \DateTime($dateEnabledFrom, new \DateTimeZone('UTC'))
                : false;
        }

        return $this->enabledFrom;
    }

    /**
     * @param string $date
     *
     * @return void
     */
    public function setDateEnabledFrom(string $date): void
    {
        $this->registry->setValue('/server/maintenance/schedule/date/enabled/from/', $date);
        $this->enabledFrom = new \DateTime($date, new \DateTimeZone('UTC'));
    }

    /**
     * @return \DateTime|false|null
     * @throws \Exception
     */
    public function getDateEnabledTo()
    {
        if ($this->enabledTo === null) {
            $dateEnabledTo = $this->registry->getValue(
                '/server/maintenance/schedule/date/enabled/to/'
            );
            $this->enabledTo = $dateEnabledTo
                ? new \DateTime($dateEnabledTo, new \DateTimeZone('UTC'))
                : false;
        }

        return $this->enabledTo;
    }

    /**
     * @param string $date
     *
     * @return void
     * @throws \Exception
     */
    public function setDateEnabledTo(string $date): void
    {
        $this->registry->setValue('/server/maintenance/schedule/date/enabled/to/', $date);
        $this->enabledTo = new \DateTime($date, new \DateTimeZone('UTC'));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function processUnexpectedMaintenance(): void
    {
        if ($this->isNow()) {
            return;
        }

        $to = new \DateTime('now', new \DateTimeZone('UTC'));
        $to->modify('+ 10 minutes');
        // @codingStandardsIgnoreLine
        $to->modify('+' . call_user_func('mt_rand', 0, 300) . ' second');

        $this->setDateEnabledFrom((new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
        $this->setDateEnabledTo($to->format('Y-m-d H:i:s'));
    }
}
