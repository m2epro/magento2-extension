<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Database\MysqlInfo;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;
use Ess\M2ePro\Model\M2ePro\Connector\Tables\Get\Diff as Connector;

class TablesStructure extends IssueType
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
        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getConnector('tables','get','diff');

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
The critical issue with the Database used by M2E Pro is detected.
Some MySQL tables or some structural elements of Database are missing.
In this case, any of the functionality available in M2E Pro may experience problems with its working.
This issue might be caused by failed Installation/Upgrade processes of M2E Pro Module.
We recommend you to contact our Support Team via email <a href="mailto:support@m2epro.com">support@m2epro.com</a>
for assistance in this matter.
HTML
            ]));
            return;
        }

        if ($taskResult->getTaskResult() < TaskResult::STATE_WARNING
            && $diffResult['severity'] == Connector::SEVERITY_WARNING) {

            $taskResult->setTaskResult(TaskResult::STATE_WARNING);
            $taskResult->setTaskMessage($this->getHelper('Module\Translation')->translate([
<<<HTML
Your current Database structure which is used in M2E Pro has differences from the database structure
of the current M2E Pro version. It is not a critical issue which affect the working state of M2E Pro Module.
However, if you face any unusual behavior of the Module, please, contact our Support Team via email
<a href="mailto:support@m2epro.com">support@m2epro.com</a> with the detailed description of the problem you faced.
HTML
            ]));
            return;
        }
    }

    //########################################
}