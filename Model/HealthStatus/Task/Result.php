<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task;

class Result extends \Ess\M2ePro\Model\AbstractModel
{
    const STATE_CRITICAL = 40;
    const STATE_WARNING  = 30;
    const STATE_NOTICE   = 20;
    const STATE_SUCCESS  = 10;

    private $taskHash;
    private $taskType;
    private $taskMustBeShownIfSuccess;

    private $tabName;
    private $fieldSetName;
    private $fieldName;

    private $taskResult  = self::STATE_SUCCESS;
    private $taskMessage = '';
    private $taskData    = [];

    //########################################

    public function __construct(
        $taskHash, $taskType, $taskMustBeShownIfSuccess,
        $tabName, $fieldSetName, $fieldName,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ){
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->taskHash = $taskHash;
        $this->taskType = $taskType;
        $this->taskMustBeShownIfSuccess = $taskMustBeShownIfSuccess;

        $this->tabName = $tabName;
        $this->fieldSetName = $fieldSetName;
        $this->fieldName = $fieldName;
    }

    //########################################

    public function getTaskHash()
    {
        return $this->taskHash;
    }

    public function getTaskType()
    {
        return $this->taskType;
    }

    public function isTaskMustBeShowIfSuccess()
    {
        return $this->taskMustBeShownIfSuccess;
    }

    //----------------------------------------

    public function getTabName()
    {
        return $this->tabName;
    }

    public function getFieldSetName()
    {
        return $this->fieldSetName;
    }

    public function getFieldName()
    {
        return $this->fieldName;
    }

    //----------------------------------------

    public function setTaskResult($value)
    {
        $this->taskResult = $value;
        return $this;
    }

    public function getTaskResult()
    {
        return $this->taskResult;
    }

    //----------------------------------------

    public function setTaskMessage($message)
    {
        $this->taskMessage = $message;
        return $this;
    }

    public function getTaskMessage()
    {
        return $this->taskMessage;
    }

    //----------------------------------------

    public function setTaskData($data)
    {
        $this->taskData = $data;
        return $this;
    }

    public function getTaskData()
    {
        return $this->taskData;
    }

    //########################################

    public function isCritical()
    {
        return $this->getTaskResult() == self::STATE_CRITICAL;
    }

    public function isWaring()
    {
        return $this->getTaskResult() == self::STATE_WARNING;
    }

    public function isNotice()
    {
        return $this->getTaskResult() == self::STATE_NOTICE;
    }

    public function isSuccess()
    {
        return $this->getTaskResult() == self::STATE_SUCCESS;
    }

    //----------------------------------------

    public function isTaskTypeIssue()
    {
        return $this->getTaskType() == IssueType::TYPE;
    }

    public function isTaskTypeInfo()
    {
        return $this->getTaskType() == InfoType::TYPE;
    }

    //########################################
}