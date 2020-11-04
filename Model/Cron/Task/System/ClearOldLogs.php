<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\System;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\System\ClearOldLogs
 */
class ClearOldLogs extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'system/clear_old_logs';

    /**
     * @var int (in seconds)
     */
    protected $interval = 86400;

    const SYSTEM_LOG_MAX_DAYS = 30;
    const SYSTEM_LOG_MAX_RECORDS = 100000;

    //########################################

    protected function performActions()
    {
        /** @var $tempModel \Ess\M2ePro\Model\Log\Clearing */
        $tempModel = $this->modelFactory->getObject('Log\Clearing');

        $tempModel->clearOldRecords(\Ess\M2ePro\Model\Log\Clearing::LOG_LISTINGS);
        $tempModel->clearOldRecords(\Ess\M2ePro\Model\Log\Clearing::LOG_SYNCHRONIZATIONS);
        $tempModel->clearOldRecords(\Ess\M2ePro\Model\Log\Clearing::LOG_ORDERS);

        $this->clearSystemLog();

        $this->activeRecordFactory->getObject('Cron_OperationHistory')->cleanOldData();

        return true;
    }

    //########################################

    protected function clearSystemLog()
    {
        $this->clearSystemLogByAmount();
        $this->clearSystemLogByTime();
    }

    // ---------------------------------------

    protected function clearSystemLogByAmount()
    {
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_system_log');

        $counts = (int)$this->resource->getConnection()->select()
                                      ->from($tableName, [new \Zend_Db_Expr('COUNT(*)')])
                                      ->query()
                                      ->fetchColumn();

        if ($counts <= self::SYSTEM_LOG_MAX_RECORDS) {
            return;
        }

        $connection = $this->resource->getConnection();

        $ids = $connection->select()
            ->from($tableName, 'id')
            ->limit($counts - self::SYSTEM_LOG_MAX_RECORDS)
            ->order(['id ASC'])
            ->query()
            ->fetchAll(\Zend_Db::FETCH_COLUMN);

        $connection->delete($tableName, 'id IN ('.implode(',', $ids).')');
    }

    protected function clearSystemLogByTime()
    {
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_system_log');

        $currentDate = $this->getHelper('Data')->getCurrentGmtDate();
        $dateTime = new \DateTime($currentDate, new \DateTimeZone('UTC'));
        $dateTime->modify('-'.self::SYSTEM_LOG_MAX_DAYS.' days');
        $minDate = $dateTime->format('Y-m-d 00:00:00');

        $this->resource->getConnection()->delete($tableName, "create_date < '{$minDate}'");
    }

    //########################################
}
