<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Date
{
    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    private static $timezone;
    /** @var \Magento\Framework\Locale\ResolverInterface */
    private static $localeResolver;

    // ----------------------------------------

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public static function createCurrentGmt(): \DateTime
    {
        return self::createDateGmt('now');
    }

    /**
     * @param string|null $date
     *
     * @return \DateTime
     * @throws \Exception
     */
    public static function createDateGmt($date): \DateTime
    {
        // for backward compatibility
        if ($date === null) {
            $date = 'now';
        }

        return new \DateTime($date, new \DateTimeZone(self::getTimezone()->getDefaultTimezone()));
    }

    /**
     * @return \DateTime
     * @throws \Exception
     */
    public static function createCurrentInCurrentZone(): \DateTime
    {
        return self::createDateInCurrentZone('now');
    }

    /**
     * @param string $date
     *
     * @return \DateTime
     * @throws \Exception
     */
    public static function createDateInCurrentZone(string $date): \DateTime
    {
        return new \DateTime($date, new \DateTimeZone(self::getTimezone()->getConfigTimezone()));
    }

    /**
     * @deprecated
     * @see self::createDateGmt
     * @param string $timeString
     *
     * @return \DateTime
     */
    public function createGmtDateTime($timeString): \DateTime
    {
        return self::createDateGmt($timeString);
    }

    // ---------------------------------------

    /**
     * @deprecated
     * @param bool $returnTimestamp
     * @param string $format
     *
     * @return int|string
     */
    public function getCurrentGmtDate($returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        $dateObject = self::createCurrentGmt();

        if ($returnTimestamp) {
            return $dateObject->getTimestamp();
        }

        return $dateObject->format($format);
    }

    /**
     * @deprecated
     * @see use explicitly self::createCurrentInCurrentZone
     * @param bool $returnTimestamp
     * @param string $format
     *
     * @return int|string
     */
    public function getCurrentTimezoneDate($returnTimestamp = false, $format = 'Y-m-d H:i:s')
    {
        $dateObject = self::createCurrentInCurrentZone();

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
        $dateObject = self::createDateGmt($date);
        $dateObject->setTimezone(new \DateTimeZone(self::getTimezone()->getConfigTimezone()));

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
        $dateObject = self::createDateInCurrentZone($date);
        $dateObject->setTimezone(new \DateTimeZone(self::getTimezone()->getDefaultTimezone()));

        if ($returnTimestamp) {
            return $dateObject->getTimestamp();
        }

        return $dateObject->format($format);
    }

    // ---------------------------------------

    /**
     * @param $localDate
     * @param $localIntlDateFormat
     * @param $localIntlTimeFormat
     * @param $localTimezone
     *
     * @return false|float|int
     */
    public static function parseDateFromLocalFormat(
        $localDate,
        $localIntlDateFormat = \IntlDateFormatter::SHORT,
        $localIntlTimeFormat = \IntlDateFormatter::SHORT,
        $localTimezone = null
    ) {
        if ($localTimezone === null) {
            $localTimezone = self::getTimezone()->getConfigTimezone();
        }

        $pattern = '';
        if ($localIntlDateFormat !== \IntlDateFormatter::NONE) {
            $pattern = self::getTimezone()->getDateFormat($localIntlDateFormat);
        }

        if ($localIntlTimeFormat !== \IntlDateFormatter::NONE) {
            $timeFormat = self::getTimezone()->getTimeFormat($localIntlTimeFormat);
            $pattern = empty($pattern) ? $timeFormat : $pattern . ' ' . $timeFormat;
        }

        $formatter = new \IntlDateFormatter(
            self::getLocaleResolver()->getLocale(),
            $localIntlDateFormat,
            $localIntlTimeFormat,
            new \DateTimeZone($localTimezone),
            null,
            $pattern
        );

        return $formatter->parse($localDate);
    }

    // ----------------------------------------

    /**
     * @return \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public static function getTimezone(): \Magento\Framework\Stdlib\DateTime\TimezoneInterface
    {
        if (isset(self::$timezone)) {
            return self::$timezone;
        }

        return self::$timezone = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);
    }

    /**
     * @return \Magento\Framework\Locale\ResolverInterface
     */
    public static function getLocaleResolver(): \Magento\Framework\Locale\ResolverInterface
    {
        if (isset(self::$localeResolver)) {
            return self::$localeResolver;
        }

        return self::$localeResolver = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Locale\ResolverInterface::class);
    }
}
