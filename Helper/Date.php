<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Date
{
    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private $timezone;
    /** @var \Magento\Framework\Locale\ResolverInterface */
    private $localeResolver;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Locale\ResolverInterface $localeResolver
    ) {
        $this->timezone = $timezone;
        $this->localeResolver = $localeResolver;
    }

    /**
     * @return string
     */
    public function getConfigTimezone(): string
    {
        return $this->timezone->getConfigTimezone();
    }

    /**
     * @return string
     */
    public function getDefaultTimezone(): string
    {
        return $this->timezone->getDefaultTimezone();
    }

    // ---------------------------------------

    /**
     * @param string $timeString
     *
     * @return \DateTime
     */
    public function createGmtDateTime($timeString): \DateTime
    {
        return new \DateTime($timeString, new \DateTimeZone($this->getDefaultTimezone()));
    }

    /**
     * @return \DateTime
     */
    public function createCurrentGmtDateTime(): \DateTime
    {
        return $this->createGmtDateTime('now');
    }

    // ---------------------------------------

    /**
     * @param bool $returnTimestamp
     * @param string $format
     *
     * @return int|string
     */
    public function getCurrentGmtDate($returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        $dateObject = $this->createCurrentGmtDateTime();

        if ($returnTimestamp) {
            return $dateObject->getTimestamp();
        }

        return $dateObject->format($format);
    }

    /**
     * @param bool $returnTimestamp
     * @param string $format
     *
     * @return int|string
     */
    public function getCurrentTimezoneDate($returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        $dateObject = new \DateTime('now', new \DateTimeZone($this->getConfigTimezone()));

        if ($returnTimestamp) {
            return $dateObject->getTimestamp();
        }

        return $dateObject->format($format);
    }

    // ---------------------------------------

    /**
     * @param string $date
     * @param bool $returnTimestamp
     * @param string $format
     *
     * @return int|string
     */
    public function gmtDateToTimezone($date, $returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        $dateObject = $this->createGmtDateTime($date);
        $dateObject->setTimezone(new \DateTimeZone($this->getConfigTimezone()));

        if ($returnTimestamp) {
            return $dateObject->getTimestamp();
        }

        return $dateObject->format($format);
    }

    /**
     * @param string $date
     * @param bool $returnTimestamp
     * @param string $format
     *
     * @return int|string
     */
    public function timezoneDateToGmt($date, $returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        $dateObject = new \DateTime($date, new \DateTimeZone($this->getConfigTimezone()));
        $dateObject->setTimezone(new \DateTimeZone($this->getDefaultTimezone()));

        if ($returnTimestamp) {
            return $dateObject->getTimestamp();
        }

        return $dateObject->format($format);
    }

    // ---------------------------------------

    /**
     * @param string $localDate
     * @param int $localIntlDateFormat
     * @param int $localIntlTimeFormat
     * @param string|null $localTimezone
     *
     * @return false|float|int
     */
    public function parseTimestampFromLocalizedFormat(
        $localDate,
        $localIntlDateFormat = \IntlDateFormatter::SHORT,
        $localIntlTimeFormat = \IntlDateFormatter::SHORT,
        $localTimezone = null
    ) {
        $localTimezone === null && $localTimezone = $this->timezone->getConfigTimezone();

        $pattern = '';
        if ($localIntlDateFormat !== \IntlDateFormatter::NONE) {
            $pattern = $this->timezone->getDateFormat($localIntlDateFormat);
        }
        if ($localIntlTimeFormat !== \IntlDateFormatter::NONE) {
            $timeFormat = $this->timezone->getTimeFormat($localIntlTimeFormat);
            $pattern = empty($pattern) ? $timeFormat : $pattern . ' ' . $timeFormat;
        }

        $formatter = new \IntlDateFormatter(
            $this->localeResolver->getLocale(),
            $localIntlDateFormat,
            $localIntlTimeFormat,
            new \DateTimeZone($localTimezone),
            null,
            $pattern
        );

        return $formatter->parse($localDate);
    }
}
