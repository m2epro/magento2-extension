<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner\Service;

/**
 * Class \Ess\M2ePro\Model\Cron\Runner\Service\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Cron\Runner\AbstractModel
{
    protected $requestAuthKey;
    protected $requestConnectionId;

    //########################################

    public function getInitiator()
    {
        return \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Cron\Strategy\AbstractModel
     */
    protected function getStrategyObject()
    {
        return $this->modelFactory->getObject('Cron_Strategy_Parallel');
    }

    //########################################

    public function setRequestAuthKey($value)
    {
        $this->requestAuthKey = $value;
    }

    public function setRequestConnectionId($value)
    {
        $this->requestConnectionId = $value;
    }

    //########################################

    protected function isPossibleToRun()
    {
        if (!parent::isPossibleToRun()) {
            return false;
        }

        $authKey = $this->getHelper('Module')->getConfig()->getGroupValue('/cron/service/', 'auth_key');
        if ($authKey === null || $this->requestAuthKey === null || $this->requestConnectionId === null) {
            return false;
        }

        return $authKey == $this->requestAuthKey;
    }

    //########################################

    protected function getOperationHistoryData()
    {
        return array_merge(parent::getOperationHistoryData(), [
            'auth_key'      => $this->requestAuthKey,
            'connection_id' => $this->requestConnectionId
        ]);
    }

    //########################################

    public function resetTasksStartFrom()
    {
        $this->resetTaskStartFrom('servicing');
        $this->resetTaskStartFrom('synchronization');
    }

    protected function resetTaskStartFrom($taskName)
    {
        $config = $this->getHelper('Module')->getConfig();

        $startDate = new \DateTime($this->getHelper('Data')->getCurrentGmtDate(), new \DateTimeZone('UTC'));
        $shift = 60 + rand(0, (int)$config->getGroupValue('/cron/task/'.$taskName.'/', 'interval'));
        $startDate->modify('+'.$shift.' seconds');

        $config->setGroupValue('/cron/task/'.$taskName.'/', 'start_from', $startDate->format('Y-m-d H:i:s'));
    }

    //########################################
}
