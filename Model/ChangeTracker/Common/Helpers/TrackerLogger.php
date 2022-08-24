<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\Helpers;

use Ess\M2ePro\Helper\Date;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class TrackerLogger implements LoggerInterface
{
    private const REGISTRY_KEY = '/change_tracker/logs';

    /** @var int */
    private $level;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;
    /** @var array */
    private $tmpLogs = [];

    /**
     * @param \Ess\M2ePro\Helper\Module\ChangeTracker $changeTrackerHelper
     * @param \Ess\M2ePro\Model\Registry\Manager $registry
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\ChangeTracker $changeTrackerHelper,
        \Ess\M2ePro\Model\Registry\Manager $registry
    ) {
        $this->level = $changeTrackerHelper->getLogLevel();
        $this->registry = $registry;
    }

    public function __destruct()
    {
        $values  = $this->registry->getValueFromJson(self::REGISTRY_KEY) ?: [];
        array_unshift($values, $this->tmpLogs);
        $values = array_slice($values, 0, 5);
        $this->registry->setValue(self::REGISTRY_KEY, $values);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(Logger::EMERGENCY, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log(Logger::ALERT, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log(Logger::CRITICAL, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log(Logger::ERROR, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log(Logger::WARNING, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log(Logger::NOTICE, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log(Logger::INFO, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->log(Logger::DEBUG, $message, $context);
    }

    /**
     * @param int $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {

        if ($this->level > $level) {
            return;
        }

        $this->tmpLogs[] = [
            'date' => Date::createCurrentGmt()->format('Y-m-d H:i:s'),
            'level' => Logger::getLevelName($level),
            'message' => $message,
            'context' => $this->formatContext($context)
        ];
    }

    /**
     * @param array $context
     *
     * @return string
     */
    private function formatContext(array $context): string
    {
        array_walk($context, static function (&$item) {
            $item = preg_replace(['/\n/', '/\s+/'], ' ', $item);
        });

        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        return json_encode($context, $flags);
    }
}
