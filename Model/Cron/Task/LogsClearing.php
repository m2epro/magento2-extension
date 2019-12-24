<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\LogsClearing
 */
class LogsClearing extends AbstractModel
{
    const NICK = 'logs_clearing';
    const MAX_MEMORY_LIMIT = 128;

    const SYSTEM_LOG_MAX_DAYS = 30;
    const SYSTEM_LOG_MAX_RECORDS = 100000;

    //########################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return self::MAX_MEMORY_LIMIT;
    }

    //########################################

    protected function performActions()
    {
        /** @var $tempModel \Ess\M2ePro\Model\Log\Clearing */
        $tempModel = $this->modelFactory->getObject('Log\Clearing');

        $tempModel->clearOldRecords(\Ess\M2ePro\Model\Log\Clearing::LOG_LISTINGS);
        $tempModel->clearOldRecords(\Ess\M2ePro\Model\Log\Clearing::LOG_OTHER_LISTINGS);
        $tempModel->clearOldRecords(\Ess\M2ePro\Model\Log\Clearing::LOG_SYNCHRONIZATIONS);
        $tempModel->clearOldRecords(\Ess\M2ePro\Model\Log\Clearing::LOG_ORDERS);

        $this->clearSystemLog();

        return true;
    }

    //########################################

    private function clearSystemLog()
    {
        $this->clearSystemLogByAmount();
        $this->clearSystemLogByTime();
    }

    // ---------------------------------------

    private function clearSystemLogByAmount()
    {
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_system_log');

        $connection = $this->resource->getConnection();

        $counts = (int)$connection->select()
                                      ->from($tableName, [new \Zend_Db_Expr('COUNT(*)')])
                                      ->query()
                                      ->fetchColumn();

        if ($counts <= self::SYSTEM_LOG_MAX_RECORDS) {
            return;
        }

        $ids = $connection->select()
                              ->from($tableName, 'id')
                              ->limit($counts - self::SYSTEM_LOG_MAX_RECORDS)
                              ->order(['id ASC'])
                              ->query()
                              ->fetchAll(\Zend_Db::FETCH_COLUMN);

        $connection->delete($tableName, 'id IN ('.implode(',', $ids).')');
    }

    private function clearSystemLogByTime()
    {
        $tableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('m2epro_system_log');
        $connection = $this->resource->getConnection();

        $currentDate = $this->getHelper('Data')->getCurrentGmtDate();
        $dateTime = new \DateTime($currentDate, new \DateTimeZone('UTC'));
        $dateTime->modify('-'.self::SYSTEM_LOG_MAX_DAYS.' days');
        $minDate = $dateTime->format('Y-m-d 00:00:00');

        $connection->delete($tableName, "create_date < '{$minDate}'");
    }

    //########################################
}
