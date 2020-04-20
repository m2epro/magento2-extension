<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter;

use Ess\M2ePro\Helper\Factory;
use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime
 */
class Datetime extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Datetime
{
    /** @var Factory $helperFactory */
    protected $helperFactory = null;

    //########################################

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        DateTimeFormatterInterface $dateTimeFormatter,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        array $data = []
    ) {
        $this->helperFactory = $helperFactory;
        parent::__construct($context, $resourceHelper, $mathRandom, $localeResolver, $dateTimeFormatter, $data);
    }

    //########################################

    /**
     * Convert given date to default (UTC) timezone
     *
     * @param string $date
     * @return \DateTime|null
     */
    protected function _convertDate($date)
    {
        if (!$this->getColumn()->getFilterTime()) {
            return parent::_convertDate($date);
        }

        try {
            // todo this is not supported. Magento is always using \IntlDateFormatter::SHORT. parent::toHtml()
            // $format   = $this->getColumn()->getFormat()?: \IntlDateFormatter::SHORT;
            $format = \IntlDateFormatter::SHORT;
            $timezone = $this->getColumn()->getTimezone() !== false ? $this->_localeDate->getConfigTimezone()
                                                                    : 'UTC';

            $timeStamp = $this->helperFactory->getObject('Data')->parseTimestampFromLocalizedFormat(
                $date,
                $format,
                $format,
                $timezone
            );

            if (empty($timeStamp)) {
                return null;
            }

            $simpleRes = new \DateTime(null, new \DateTimeZone($timezone));
            $simpleRes->setTimestamp($timeStamp);
            $simpleRes->setTimezone(new \DateTimeZone('UTC'));

            return $simpleRes;
        } catch (\Exception $e) {
            return null;
        }
    }

    //########################################
}
