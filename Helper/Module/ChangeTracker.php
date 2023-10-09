<?php

namespace Ess\M2ePro\Helper\Module;

class ChangeTracker
{
    public const STATUS_DISABLED = 0;
    public const STATUS_ENABLED = 1;
    public const DEFAULT_RUN_INTERVAL = 3600;

    private const CHANGE_TRACKER_CONFIG_GROUP = '/change_tracker/';
    private const DEFAULT_LOG_LEVEL = 200;

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    public function __construct(\Ess\M2ePro\Model\Config\Manager $config)
    {
        $this->config = $config;
    }

    public function getStatus(): int
    {
        return (int)($this->getValue('status') ?? self::STATUS_DISABLED) ;
    }

    public function setStatus(int $status): bool
    {
        return $this->setValue('status', $status);
    }

    public function getInterval(): int
    {
        return (int)($this->getValue('run_interval') ?? self::DEFAULT_RUN_INTERVAL);
    }

    public function setInterval(int $seconds): bool
    {
        return $this->setValue('run_interval', $seconds);
    }

    public function getLogLevel(): int
    {
        return (int)($this->getValue('log_level') ?? self::DEFAULT_LOG_LEVEL);
    }

    public function setLogLevel(int $logLevel): bool
    {
        return $this->setValue('log_level', $logLevel);
    }

    public function isEnabled(): bool
    {
        return $this->getStatus() === self::STATUS_ENABLED;
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
        return $this->config->setGroupValue(self::CHANGE_TRACKER_CONFIG_GROUP, $key, $value);
    }
}
