<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Cron extends \Ess\M2ePro\Helper\AbstractHelper
{
    const RUNNER_MAGENTO = 'magento';
    const RUNNER_SERVICE = 'service';

    const STRATEGY_SERIAL   = 'serial';
    const STRATEGY_PARALLEL = 'parallel';

    protected $moduleConfig;
    protected $modelFactory;
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->moduleConfig = $moduleConfig;
        $this->modelFactory = $modelFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function isModeEnabled()
    {
        return (bool)$this->getConfigValue('mode');
    }

    //########################################

    public function getRunner()
    {
        return $this->getConfigValue('runner');
    }

    public function setRunner($value)
    {
        if ($this->getRunner() != $value) {
            $this->log("Cron runner was changed from [" . $this->getRunner() . "] to [" . $value . "] - ".
                        $this->getHelper('Data')->getCurrentGmtDate(), 'cron_runner_change');
        }

        return $this->setConfigValue('runner', $value);
    }

    // ---------------------------------------

    public function isRunnerMagento()
    {
        return $this->getRunner() == self::RUNNER_MAGENTO;
    }

    public function isRunnerService()
    {
        return $this->getRunner() == self::RUNNER_SERVICE;
    }

    //########################################

    public function getLastRunnerChange()
    {
        return $this->getConfigValue('last_runner_change');
    }

    public function setLastRunnerChange($value)
    {
        $this->setConfigValue('last_runner_change', $value);
    }

    // ---------------------------------------

    public function isLastRunnerChangeMoreThan($interval, $isHours = false)
    {
        $isHours && $interval *= 3600;

        $lastRunnerChange = $this->getLastRunnerChange();
        if (is_null($lastRunnerChange)) {
            return false;
        }

        return $this->getHelper('Data')->getCurrentGmtDate(true) > strtotime($lastRunnerChange) + $interval;
    }

    //########################################

    public function getLastAccess()
    {
        return $this->getConfigValue('last_access');
    }

    public function setLastAccess($value)
    {
        return $this->setConfigValue('last_access',$value);
    }

    // ---------------------------------------

    public function isLastAccessMoreThan($interval, $isHours = false)
    {
        $isHours && $interval *= 3600;

        $lastAccess = $this->getLastAccess();
        if (is_null($lastAccess)) {
            return false;
        }

        return $this->getHelper('Data')->getCurrentGmtDate(true) > strtotime($lastAccess) + $interval;
    }

    //########################################

    public function getLastRun()
    {
        return $this->getConfigValue('last_run');
    }

    public function setLastRun($value)
    {
        return $this->setConfigValue('last_run',$value);
    }

    // ---------------------------------------

    public function isLastRunMoreThan($interval, $isHours = false)
    {
        $isHours && $interval *= 3600;

        $lastRun = $this->getLastRun();
        if (is_null($lastRun)) {
            return false;
        }

        return $this->getHelper('Data')->getCurrentGmtDate(true) > strtotime($lastRun) + $interval;
    }

    //########################################

    public function getLastExecutedSlowTask()
    {
        return $this->getConfigValue('last_executed_slow_task');
    }

    public function setLastExecutedSlowTask($taskNick)
    {
        $this->setConfigValue('last_executed_slow_task', $taskNick);
    }

    //########################################

    private function getConfigValue($key)
    {
        return $this->moduleConfig->getGroupValue('/cron/', $key);
    }

    private function setConfigValue($key, $value)
    {
        return $this->moduleConfig->setGroupValue('/cron/', $key, $value);
    }

    //########################################

    private function log($message, $type)
    {
        /** @var \Ess\M2ePro\Model\Log\System $log */
        $log = $this->activeRecordFactory->getObject('Log\System');

        $log->setType($type);
        $log->setDescription($message);

        $log->save();
    }

    //########################################
}