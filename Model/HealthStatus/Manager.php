<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\InfoType;

class Manager extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\SetFactory */
    private $resultSetFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\SetFactory $resultSetFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ){
        parent::__construct($helperFactory, $modelFactory);
        $this->resultSetFactory = $resultSetFactory;
    }

    //########################################

    /**
     * @param $tasksType
     * @return Task\Result\Set
     */
    public function doCheck($tasksType = NULL)
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

    private function getTasks($tasksType = NULL)
    {
        switch ($tasksType) {

            case InfoType::TYPE:
                return $this->getInfoTasks();

            case IssueType::TYPE:
                return $this->getIssueTasks();

            case NULL:
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
            'Database\MysqlInfo\CrashedTables',
            'Database\MysqlInfo\TablesStructure',

            'Server\Status\GmtTime',
            'Server\Status\SystemLogs',

            'Orders\IntervalToTheLatest\Ebay',
            'Orders\IntervalToTheLatest\Amazon',
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