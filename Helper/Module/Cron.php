<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Cron
{
    public const RUNNER_MAGENTO = 'magento';
    public const RUNNER_DEVELOPER = 'developer';
    public const RUNNER_SERVICE_CONTROLLER = 'service_controller';
    public const RUNNER_SERVICE_PUB = 'service_pub';

    public const STRATEGY_SERIAL = 'serial';
    public const STRATEGY_PARALLEL = 'parallel';

    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;
    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
    ) {
        $this->helperData = $helperData;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->config = $config;
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isModeEnabled(): bool
    {
        return (bool)$this->getConfigValue('mode');
    }

    // ----------------------------------------

    public function getRunner()
    {
        return $this->getConfigValue('runner');
    }

    public function setRunner($value)
    {
        if ($this->getRunner() !== $value) {
            $this->log(
                "Cron runner was changed from [{$this->getRunner()}] to [{$value}] - " .
                $this->helperData->getCurrentGmtDate(),
                'cron_runner_change'
            );
        }

        return $this->setConfigValue('runner', $value);
    }

    // ---------------------------------------

    public function isRunnerMagento()
    {
        return $this->getRunner() == self::RUNNER_MAGENTO;
    }

    public function isRunnerServiceController()
    {
        return $this->getRunner() == self::RUNNER_SERVICE_CONTROLLER;
    }

    public function isRunnerServicePub()
    {
        return $this->getRunner() == self::RUNNER_SERVICE_PUB;
    }

    public function isRunnerService()
    {
        return $this->isRunnerServiceController() || $this->isRunnerServicePub();
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
        if ($lastRunnerChange === null) {
            return false;
        }

        $lastRunnerChangeTimestamp = (int)$this->helperData
            ->createGmtDateTime($lastRunnerChange)
            ->format('U');

        return $this->helperData->getCurrentGmtDate(true) > $lastRunnerChangeTimestamp + $interval;
    }

    //########################################

    public function getLastAccess()
    {
        return $this->getConfigValue('last_access');
    }

    public function setLastAccess($value)
    {
        return $this->setConfigValue('last_access', $value);
    }

    // ---------------------------------------

    public function isLastAccessMoreThan($interval, $isHours = false)
    {
        $isHours && $interval *= 3600;

        $lastAccess = $this->getLastAccess();
        if ($lastAccess === null) {
            return false;
        }

        $lastAccessTimestamp = (int)$this->helperData
            ->createGmtDateTime($lastAccess)
            ->format('U');

        return $this->helperData->getCurrentGmtDate(true) > $lastAccessTimestamp + $interval;
    }

    //########################################

    public function getLastRun()
    {
        return $this->getConfigValue('last_run');
    }

    public function setLastRun($value)
    {
        return $this->setConfigValue('last_run', $value);
    }

    // ---------------------------------------

    public function isLastRunMoreThan($interval, $isHours = false)
    {
        $isHours && $interval *= 3600;

        $lastRun = $this->getLastRun();
        if ($lastRun === null) {
            return false;
        }

        $lastRunTimestamp = (int)$this->helperData
            ->createGmtDateTime($lastRun)
            ->format('U');

        return $this->helperData->getCurrentGmtDate(true) > $lastRunTimestamp + $interval;
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

    //----------------------------------------

    public function getLastExecutedTaskGroup()
    {
        return $this->getConfigValue('last_executed_task_group');
    }

    public function setLastExecutedTaskGroup($groupNick)
    {
        $this->setConfigValue('last_executed_task_group', $groupNick);
    }

    //########################################

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    private function getConfigValue(string $key)
    {
        return $this->config->getGroupValue('/cron/', $key);
    }

    /**
     * @param string $key
     * @param $value
     *
     * @return bool
     */
    private function setConfigValue(string $key, $value)
    {
        return $this->config->setGroupValue('/cron/', $key, $value);
    }

    // ----------------------------------------

    /**
     * @param string $message
     * @param string $type
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function log(string $message, string $type): void
    {
        /** @var \Ess\M2ePro\Model\Log\System $log */
        $log = $this->activeRecordFactory->getObject('Log\System');

        $log->setType($type);
        $log->setDescription($message);

        $log->save();
    }
}
