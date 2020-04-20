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
    protected $_dateRealFrom;
    protected $_dateRealTo;

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
        $dateRealFrom = $this->getDateRealFrom();
        $dateRealTo = $this->getDateRealTo();

        if ($dateEnabledFrom == false ||
            $dateEnabledTo == false ||
            $dateRealFrom == false ||
            $dateRealTo == false
        ) {
            return false;
        }

        $dateCurrent = $this->getDateCurrent();
        if ($dateEnabledFrom < $dateRealFrom &&
            $dateRealFrom < $dateRealTo &&
            $dateRealTo <= $dateEnabledTo &&
            $dateEnabledFrom > $dateCurrent
        ) {
            return true;
        }

        return false;
    }

    public function isNow()
    {
        $dateEnabledFrom = $this->getDateEnabledFrom();
        $dateEnabledTo = $this->getDateEnabledTo();
        $dateRealFrom = $this->getDateRealFrom();
        $dateRealTo = $this->getDateRealTo();

        if ($dateEnabledFrom == false ||
            $dateEnabledTo == false ||
            $dateRealFrom == false ||
            $dateRealTo == false
        ) {
            return false;
        }

        $dateCurrent = $this->getDateCurrent();
        if ($dateCurrent > $dateEnabledFrom &&
            $dateCurrent < $dateEnabledTo
        ) {
            return true;
        }

        return false;
    }

    public function isInRealRange()
    {
        if (!$this->isNow()) {
            return false;
        }

        $dateCurrent = $this->getDateCurrent();
        $dateRealFrom = $this->getDateRealFrom();
        $dateRealTo = $this->getDateRealTo();

        return $dateCurrent >= $dateRealFrom && $dateCurrent <= $dateRealTo;
    }

    //########################################

    public function getDateCurrent()
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function getDateEnabledFrom()
    {
        if ($this->_dateEnabledFrom === null) {
            $this->_dateEnabledFrom = $this->getDateByKey('/server/maintenance/schedule/date/enabled/from/');
        }

        return $this->_dateEnabledFrom;
    }

    public function getDateEnabledTo()
    {
        if ($this->_dateEnabledTo === null) {
            $this->_dateEnabledTo = $this->getDateByKey('/server/maintenance/schedule/date/enabled/to/');
        }

        return $this->_dateEnabledTo;
    }

    public function getDateRealFrom()
    {
        if ($this->_dateRealFrom === null) {
            $this->_dateRealFrom = $this->getDateByKey('/server/maintenance/schedule/date/real/from/');
        }

        return $this->_dateRealFrom;
    }

    public function getDateRealTo()
    {
        if ($this->_dateRealTo === null) {
            $this->_dateRealTo = $this->getDateByKey('/server/maintenance/schedule/date/real/to/');
        }

        return $this->_dateRealTo;
    }

    //########################################

    protected function getDateByKey($key)
    {
        /** @var \Ess\M2ePro\Model\Registry $date */
        $date = $this->activeRecordFactory->getObject('Registry');
        $date->loadByKey($key);

        $value = $date->getValue();

        if (empty($value)) {
            return false;
        }

        return new \DateTime($date->getValue(), new \DateTimeZone('UTC'));
    }

    //########################################
}
