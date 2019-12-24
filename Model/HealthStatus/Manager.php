<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\InfoType;

/**
 * Class \Ess\M2ePro\Model\HealthStatus\Manager
 */
class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\SetFactory */
    private $resultSetFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\SetFactory $resultSetFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->resultSetFactory = $resultSetFactory;
    }

    //########################################

    /**
     * @param $tasksType
     * @return Task\Result\Set
     */
    public function doCheck($tasksType = null)
    {
        $resultSet = $this->resultSetFactory->create();

        foreach ($this->getTasks($tasksType) as $taskNick) {
            try {
                $taskObject = $this->buildTaskObject($taskNick);
                $resultSet->add($taskObject->process());
            } catch (\Throwable $throwable) {
                $this->getHelper('Module\Exception')->process($throwable);
            } catch (\Exception $exception) {
                $this->getHelper('Module\Exception')->process($exception);
            }
        }

        return $resultSet;
    }

    //########################################

    private function getTasks($tasksType = null)
    {
        switch ($tasksType) {
            case InfoType::TYPE:
                return $this->getInfoTasks();

            case IssueType::TYPE:
                return $this->getIssueTasks();

            case null:
            default:
                return array_merge($this->getInfoTasks(), $this->getIssueTasks());
        }
    }

    private function getInfoTasks()
    {
        return [];
    }

    private function getIssueTasks()
    {
        return [
            'Database_MysqlInfo_CrashedTables',
            'Database_MysqlInfo_TablesStructure',

            'Server_Status_GmtTime',
            'Server_Status_SystemLogs',

            'Orders_IntervalToTheLatest_Ebay',
            'Orders_IntervalToTheLatest_Amazon',
            'Orders_MagentoCreationFailed_Ebay',
            'Orders_MagentoCreationFailed_Amazon',
        ];
    }

    //----------------------------------------

    /**
     * @param $taskNick
     * @return \Ess\M2ePro\Model\HealthStatus\Task\AbstractModel
     */
    private function buildTaskObject($taskNick)
    {
        $taskNick = 'HealthStatus\Task\\' . $taskNick;
        $model = $this->modelFactory->getObject($taskNick);

        return $model;
    }

    //########################################
}
