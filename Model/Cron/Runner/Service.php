<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Runner;

use \Ess\M2ePro\Helper\Module\Cron\Service as CronService;

class Service extends AbstractModel
{
    private $requestAuthKey      = NULL;
    private $requestConnectionId = NULL;

    //########################################

    protected function getNick()
    {
        return \Ess\M2ePro\Helper\Module\Cron::RUNNER_SERVICE;
    }

    protected function getInitiator()
    {
        return \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION;
    }

    //########################################

    public function process()
    {
        if ($this->getHelper('Module')->getConfig()->getGroupValue('/cron/service/','disabled')) {
            return false;
        }

        return parent::process();
    }

    /**
     * @return \Ess\M2ePro\Model\Cron\Strategy\AbstractModel
     */
    protected function getStrategyObject()
    {
        return $this->modelFactory->getObject('Cron\Strategy\Parallel');
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

    // ---------------------------------------

    public function resetTasksStartFrom()
    {
        $this->resetTaskStartFrom('servicing');
        $this->resetTaskStartFrom('synchronization');
    }

    //########################################

    protected function initialize()
    {
        parent::initialize();

        $helper = $this->getHelper('Module\Cron');

        if ($helper->isRunnerService()) {

            $helper->isLastAccessMoreThan(CronService::MAX_INACTIVE_TIME) && $this->resetTasksStartFrom();
            return;
        }

        $helper->setRunner(\Ess\M2ePro\Helper\Module\Cron::RUNNER_SERVICE);
        $helper->setLastRunnerChange($this->getHelper('Data')->getCurrentGmtDate());

        $this->resetTasksStartFrom();
    }

    protected function isPossibleToRun()
    {
        $authKey = $this->getHelper('Module')->getConfig()
                        ->getGroupValue('/cron/service/','auth_key');

        return !is_null($authKey) &&
               !is_null($this->requestAuthKey) &&
               !is_null($this->requestConnectionId) &&
               $authKey == $this->requestAuthKey &&
               parent::isPossibleToRun();
    }

    //########################################

    protected function getOperationHistoryData()
    {
        return array_merge(parent::getOperationHistoryData(), array(
            'auth_key'      => $this->requestAuthKey,
            'connection_id' => $this->requestConnectionId
        ));
    }

    //########################################

    private function resetTaskStartFrom($taskName)
    {
        $config = $this->getHelper('Module')->getConfig();

        $startDate = new \DateTime($this->getHelper('Data')->getCurrentGmtDate(), new \DateTimeZone('UTC'));
        $shift = 60 + rand(0,(int)$config->getGroupValue('/cron/task/'.$taskName.'/','interval'));
        $startDate->modify('+'.$shift.' seconds');

        $config->setGroupValue('/cron/task/'.$taskName.'/','start_from',$startDate->format('Y-m-d H:i:s'));
    }

    //########################################
}