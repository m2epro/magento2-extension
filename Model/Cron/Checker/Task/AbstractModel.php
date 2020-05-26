<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Checker\Task;

/**
 * Class \Ess\M2ePro\Model\Cron\Checker\Task\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    const NICK = null;

    /**
     * @var int (in seconds)
     */
    protected $interval = 3600;

    //########################################

    abstract public function performActions();

    //########################################

    public function process()
    {
        if (!$this->isPossibleToRun()) {
            return;
        }

        $this->updateLastRun();
        $this->performActions();
    }

    //########################################

    protected function getNick()
    {
        $nick = static::NICK;
        if (empty($nick)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Task NICK is not defined.');
        }

        return $nick;
    }

    //########################################

    /**
     * @return bool
     */
    public function isPossibleToRun()
    {
        return $this->isIntervalExceeded();
    }

    /**
     * @return bool
     */
    protected function isIntervalExceeded()
    {
        $lastRun = $this->getConfigValue('last_run');

        if ($lastRun === null) {
            return true;
        }

        $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);
        return $currentTimeStamp > strtotime($lastRun) + $this->getInterval();
    }

    public function getInterval()
    {
        $interval = $this->getConfigValue('interval');
        return $interval === null ? $this->interval : (int)$interval;
    }

    // ---------------------------------------

    protected function updateLastRun()
    {
        $this->setCacheConfigValue('last_run', $this->getHelper('Data')->getCurrentGmtDate());
    }

    //########################################

    protected function getConfig()
    {
        return $this->getHelper('Module')->getConfig();
    }

    protected function getCacheConfig()
    {
        return $this->getHelper('Module')->getCacheConfig();
    }

    protected function getConfigGroup()
    {
        return '/cron/checker/task/'.$this->getNick().'/';
    }

    // ---------------------------------------

    protected function getConfigValue($key)
    {
        return $this->getConfig()->getGroupValue($this->getConfigGroup(), $key);
    }

    protected function setConfigValue($key, $value)
    {
        return $this->getConfig()->setGroupValue($this->getConfigGroup(), $key, $value);
    }

    // ---------------------------------------

    protected function setCacheConfigValue($key, $value)
    {
        return $this->getCacheConfig()->setGroupValue($this->getConfigGroup(), $key, $value);
    }

    protected function getCacheConfigValue($key)
    {
        return $this->getCacheConfig()->getGroupValue($this->getConfigGroup(), $key);
    }

    //########################################
}
