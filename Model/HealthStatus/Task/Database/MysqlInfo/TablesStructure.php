<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Database\MysqlInfo;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;
use Ess\M2ePro\Model\M2ePro\Connector\Tables\Get\Diff as Connector;

/**
 * Class \Ess\M2ePro\Model\HealthStatus\Task\Database\MysqlInfo\TablesStructure
 */
class TablesStructure extends IssueType
{
    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory */
    private $resultFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory $resultFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->resultFactory = $resultFactory;
    }

    //########################################

    public function process()
    {
        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getConnector('tables', 'get', 'diff');

        $dispatcherObject->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        $result = $this->resultFactory->create($this);
        $result->setTaskResult(TaskResult::STATE_SUCCESS);

        if (!isset($responseData['diff']) || count($responseData['diff']) <= 0) {
            return $result;
        }

        foreach ($responseData['diff'] as $tableName => $checkingResults) {
            foreach ($checkingResults as $resultRow) {
                $this->applyDiffResult($result, $resultRow);
            }
        }

        return $result;
    }

    //########################################

    private function applyDiffResult(TaskResult $taskResult, $diffResult)
    {
        if ($taskResult->getTaskResult() < TaskResult::STATE_CRITICAL
            && $diffResult['severity'] == Connector::SEVERITY_CRITICAL) {
            $taskResult->setTaskResult(TaskResult::STATE_CRITICAL);
            $taskResult->setTaskMessage($this->getHelper('Module\Translation')->translate([
            <<<HTML
Some MySQL tables or their columns are missing. It can cause critical issues in Module work. 
Please contact Support at <a href="mailto:support@m2epro.com">support@m2epro.com</a> for a solution.
HTML
            ]));
            return;
        }

        if ($taskResult->getTaskResult() < TaskResult::STATE_WARNING
            && $diffResult['severity'] == Connector::SEVERITY_WARNING) {
            $taskResult->setTaskResult(TaskResult::STATE_WARNING);
            $taskResult->setTaskMessage($this->getHelper('Module\Translation')->translate([
            <<<HTML
Some MySQL tables or their columns may have incorrect definitions. 
If you face any unusual behavior of the Module, please contact Support at
<a href="mailto:support@m2epro.com">support@m2epro.com</a>
HTML
            ]));
            return;
        }
    }

    //########################################
}
