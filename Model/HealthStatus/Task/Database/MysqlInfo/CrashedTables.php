<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Database\MysqlInfo;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;

class CrashedTables extends IssueType
{
    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory */
    private $resultFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory $resultFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ){
        parent::__construct($helperFactory, $modelFactory);
        $this->resultFactory = $resultFactory;
    }

    //########################################

    public function process()
    {
        /** @var \Ess\M2ePro\Helper\Module\Database\Structure $helper */
        $helper = $this->helperFactory->getObject('Module\Database\Structure');

        $crashedTables = [];
        foreach ($helper->getMySqlTables() as $tableName) {
            if (!$helper->isTableStatusOk($tableName)) {
                $crashedTables[] = $tableName;
            }
        }

        $result = $this->resultFactory->create($this);
        $result->setTaskData($crashedTables)
               ->setTaskMessage($this->getTaskMessage($crashedTables));

        empty($crashedTables) ? $result->setTaskResult(TaskResult::STATE_SUCCESS)
                              : $result->setTaskResult(TaskResult::STATE_CRITICAL);

        return $result;
    }

    //########################################

    private function getTaskMessage($crashedTables)
    {
        if (empty($crashedTables)) {
            return '';
        }

        return implode(', ', $crashedTables);
    }

    //########################################
}