<?php

namespace Ess\M2ePro\Helper\Module;

class ChangeTracker
{
    private const CHANGE_TRACKER_CONFIG_GROUP = '/change_tracker/';
    private const DEFAULT_STATUS = 0;
    private const DEFAULT_LOG_LEVEL = 200;
    private const DEFAULT_RUN_INTERVAL = 3600;

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Ess\M2ePro\Model\Config\Manager $config
     */
    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $config
    ) {
        $this->config = $config;
    }

    // ----------------------------------------

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return (int)($this->getValue('status') ?? self::DEFAULT_STATUS) ;
    }

    /**
     * @param int $status
     *
     * @return bool
     */
    public function setStatus(int $status): bool
    {
        return $this->setValue('status', $status);
    }

    // ----------------------------------------

    /**
     * @return int
     */
    public function getInterval(): int
    {
        return (int)($this->getValue('run_interval') ?? self::DEFAULT_RUN_INTERVAL);
    }

    /**
     * @param int $timeout
     *
     * @return bool
     */
    public function setInterval(int $timeout): bool
    {
        return $this->setValue('run_interval', $timeout);
    }

    // ----------------------------------------

    /**
     * @return int
     */
    public function getLogLevel(): int
    {
        return (int)($this->getValue('log_level') ?? self::DEFAULT_LOG_LEVEL);
    }

    /**
     * @param int $logLevel
     *
     * @return bool
     */
    public function setLogLevel(int $logLevel): bool
    {
        return $this->setValue('log_level', $logLevel);
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    private function getValue(string $key)
    {
        return $this->config->getGroupValue(self::CHANGE_TRACKER_CONFIG_GROUP, $key);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    private function setValue(string $key, $value): bool
    {
        return $this->config
            ->setGroupValue(
                self::CHANGE_TRACKER_CONFIG_GROUP,
                $key,
                $value
            );
    }
}
