<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Log;

/**
 * Class \Ess\M2ePro\Model\Log\Clearing
 */
class Clearing extends \Ess\M2ePro\Model\AbstractModel
{
    const LOG_LISTINGS          = 'listings';
    const LOG_OTHER_LISTINGS    = 'other_listings';
    const LOG_SYNCHRONIZATIONS  = 'synchronizations';
    const LOG_ORDERS            = 'orders';

    const LOG_EBAY_PICKUP_STORE = 'ebay_pickup_store';

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function clearOldRecords($log)
    {
        if (!$this->isValidLogType($log)) {
            return false;
        }

        $config = $this->getHelper('Module')->getConfig();

        $mode = $config->getGroupValue('/logs/clearing/'.$log.'/', 'mode');
        $days = $config->getGroupValue('/logs/clearing/'.$log.'/', 'days');

        $mode = (int)$mode;
        $days = (int)$days;

        if ($mode != 1 || $days <= 0) {
            return false;
        }

        $minTime = $this->getMinTimeByDays($days);
        $this->clearLogByMinTime($log, $minTime);

        return true;
    }

    public function clearAllLog($log)
    {
        if (!$this->isValidLogType($log)) {
            return false;
        }

        $timestamp = $this->getHelper('Data')->getCurrentGmtDate(true);
        $minTime = $this->getHelper('Data')->getDate($timestamp+60*60*24*365*10);

        $this->clearLogByMinTime($log, $minTime);

        return true;
    }

    // ---------------------------------------

    public function saveSettings($log, $mode, $days)
    {
        if (!$this->isValidLogType($log)) {
            return false;
        }

        $mode = (int)$mode;
        $days = (int)$days;

        if ($mode < 0 || $mode > 1) {
            $mode = 0;
        }

        if ($days <= 0) {
            $days = 90;
        }

        $config = $this->getHelper('Module')->getConfig();

        $config->setGroupValue('/logs/clearing/'.$log.'/', 'mode', $mode);
        $config->setGroupValue('/logs/clearing/'.$log.'/', 'days', $days);

        return true;
    }

    //########################################

    private function isValidLogType($log)
    {
        return $log == self::LOG_LISTINGS ||
               $log == self::LOG_OTHER_LISTINGS ||
               $log == self::LOG_SYNCHRONIZATIONS ||
               $log == self::LOG_ORDERS ||
               $log == self::LOG_EBAY_PICKUP_STORE;
    }

    private function getMinTimeByDays($days)
    {
        $timestamp = $this->getHelper('Data')->getCurrentGmtDate(true);
        $dateTimeArray = getdate($timestamp);

        $hours = $dateTimeArray['hours'];
        $minutes = $dateTimeArray['minutes'];
        $seconds = $dateTimeArray['seconds'];
        $month = $dateTimeArray['mon'];
        $day = $dateTimeArray['mday'];
        $year = $dateTimeArray['year'];

        $timeStamp = mktime($hours, $minutes, $seconds, $month, $day - $days, $year);

        return $this->getHelper('Data')->getDate($timeStamp);
    }

    private function clearLogByMinTime($log, $minTime)
    {
        $resourceModel = null;
        $connection = null;

        switch ($log) {
            case self::LOG_LISTINGS:
                $resourceModel = $this->activeRecordFactory
                              ->getObject('Listing\Log')
                              ->getResource();
                break;
            case self::LOG_OTHER_LISTINGS:
                $resourceModel = $this->activeRecordFactory
                              ->getObject('Listing_Other_Log')
                              ->getResource();
                break;
            case self::LOG_SYNCHRONIZATIONS:
                $resourceModel = $this->activeRecordFactory
                              ->getObject('Synchronization\Log')
                              ->getResource();
                break;
            case self::LOG_ORDERS:
                $resourceModel = $this->activeRecordFactory
                              ->getObject('Order\Log')
                              ->getResource();
                break;
            case self::LOG_EBAY_PICKUP_STORE:
                $resourceModel = $this->activeRecordFactory
                              ->getObject('Ebay_Account_PickupStore_Log')
                              ->getResource();
                break;
        }

        $table = $resourceModel->getMainTable();
        $connection = $resourceModel->getConnection();

        if ($table === null || $connection === null) {
            return;
        }

        $connection->delete($table, [
            ' `create_date` < ? OR `create_date` IS NULL ' => (string)$minTime]);
    }

    //########################################
}
