<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\Helpers;

use Ess\M2ePro\Helper\Date;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class TrackerLogger implements LoggerInterface
{
    public const REGISTRY_KEY = '/change_tracker/logs';

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
        $this->writeLogs();
    }

    /**
     * @throws \Exception
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(Logger::EMERGENCY, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function alert($message, array $context = []): void
    {
        $this->log(Logger::ALERT, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function critical($message, array $context = []): void
    {
        $this->log(Logger::CRITICAL, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function error($message, array $context = []): void
    {
        $this->log(Logger::ERROR, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function warning($message, array $context = []): void
    {
        $this->log(Logger::WARNING, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function notice($message, array $context = []): void
    {
        $this->log(Logger::NOTICE, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function info($message, array $context = []): void
    {
        $this->log(Logger::INFO, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function debug($message, array $context = []): void
    {
        $this->log(Logger::DEBUG, $message, $context);
    }

    /**
     * @throws \Exception
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
            'context' => $this->formatContext($context),
        ];
    }

    public function writeLogs(): void
    {
        $values = $this->registry->getValueFromJson(self::REGISTRY_KEY) ?: [];
        array_unshift($values, $this->tmpLogs);
        $values = array_slice($values, 0, 5);
        $this->registry->setValue(self::REGISTRY_KEY, $values);
    }

    private function formatContext(array $context): string
    {
        array_walk($context, static function (&$item) {
            if (!is_string($item)) {
                return;
            }

            $item = preg_replace(['/\n/', '/\s+/'], ' ', $item);
        });

        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        return json_encode($context, $flags);
    }
}
