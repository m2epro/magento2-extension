<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter;

use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;

class Datetime extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Datetime
{
    /**
     * Convert given date to default (UTC) timezone
     *
     * @param string $date
     *
     * @return \DateTime|null
     */
    protected function _convertDate($date)
    {
        $date = trim($date);
        if (
            !$this->getColumn()->getFilterTime()
            || strpos($date, ' ') === false
        ) {
            return parent::_convertDate($date);
        }

        try {
            // todo this is not supported. Magento is always using \IntlDateFormatter::SHORT. parent::toHtml()
            // $format   = $this->getColumn()->getFormat()?: \IntlDateFormatter::SHORT;
            $format = \IntlDateFormatter::SHORT;
            $timezone = $this->getColumn()->getTimezone() !== false
                ? $this->_localeDate->getConfigTimezone()
                : 'UTC';

            $timeStamp = \Ess\M2ePro\Helper\Date::parseDateFromLocalFormat(
                $date,
                $format,
                $format,
                $timezone
            );

            if (empty($timeStamp)) {
                return null;
            }

            $simpleRes = new \DateTime('now', new \DateTimeZone($timezone));
            $simpleRes->setTimestamp($timeStamp);
            $simpleRes->setTimezone(new \DateTimeZone('UTC'));

            return $simpleRes;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @ingeritdoc
     */
    public function setValue($value)
    {
        if (isset($value['locale'])) {
            if (!empty($value['from'])) {
                $value['orig_from'] = $value['from'];
                $value['from'] = $this->_convertDate($value['from']);
            }
            if (!empty($value['to'])) {
                $value['orig_to'] = $value['to'];
                $toDate = $this->_convertDate($value['to']);
                if ($toDate !== null) {
                    $toDate->setTime(
                        (int)$toDate->format('H'),
                        (int)$toDate->format('i'),
                        59
                    );
                }
                $value['to'] = $toDate;
            }
        }
        if (empty($value['from']) && empty($value['to'])) {
            $value = null;
        }
        $this->setData('value', $value);

        return $this;
    }
}
