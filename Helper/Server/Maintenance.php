<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */
namespace Ess\M2ePro\Helper\Server;

/**
 * Class \Ess\M2ePro\Helper\Server\Maintenance
 */
class Maintenance extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $_dateEnabledFrom;
    protected $_dateEnabledTo;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function isScheduled()
    {
        $dateEnabledFrom = $this->getDateEnabledFrom();
        $dateEnabledTo = $this->getDateEnabledTo();

        if ($dateEnabledFrom == false || $dateEnabledTo == false) {
            return false;
        }

        $dateCurrent = new \DateTime('now', new \DateTimeZone('UTC'));
        if ($dateCurrent < $dateEnabledFrom && $dateCurrent < $dateEnabledTo) {
            return true;
        }

        return false;
    }

    public function isNow()
    {
        $dateEnabledFrom = $this->getDateEnabledFrom();
        $dateEnabledTo = $this->getDateEnabledTo();

        if ($dateEnabledFrom == false || $dateEnabledTo == false) {
            return false;
        }

        $dateCurrent = new \DateTime('now', new \DateTimeZone('UTC'));
        if ($dateCurrent > $dateEnabledFrom && $dateCurrent < $dateEnabledTo) {
            return true;
        }

        return false;
    }

    //########################################

    public function getDateEnabledFrom()
    {
        if ($this->_dateEnabledFrom === null) {
            $dateEnabledFrom = $this->getHelper('Module')->getRegistry()->getValue(
                '/server/maintenance/schedule/date/enabled/from/'
            );
            $this->_dateEnabledFrom = $dateEnabledFrom
                ? new \DateTime($dateEnabledFrom, new \DateTimeZone('UTC'))
                : false;
        }

        return $this->_dateEnabledFrom;
    }

    public function setDateEnabledFrom($date)
    {
        $this->getHelper('Module')->getRegistry()->setValue(
            '/server/maintenance/schedule/date/enabled/from/',
            $date
        );

        $this->_dateEnabledFrom = $date;
        return $this;
    }

    public function getDateEnabledTo()
    {
        if ($this->_dateEnabledTo === null) {
            $dateEnabledTo = $this->getHelper('Module')->getRegistry()->getValue(
                '/server/maintenance/schedule/date/enabled/to/'
            );
            $this->_dateEnabledTo = $dateEnabledTo
                ? new \DateTime($dateEnabledTo, new \DateTimeZone('UTC'))
                : false;
        }

        return $this->_dateEnabledTo;
    }

    public function setDateEnabledTo($date)
    {
        $this->getHelper('Module')->getRegistry()->setValue(
            '/server/maintenance/schedule/date/enabled/to/',
            $date
        );

        $this->_dateEnabledTo = $date;
        return $this;
    }

    //########################################

    public function processUnexpectedMaintenance()
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

    //########################################
}
