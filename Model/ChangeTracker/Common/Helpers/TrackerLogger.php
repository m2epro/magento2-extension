<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Common\Helpers;

class TrackerLogger implements \Psr\Log\LoggerInterface
{
    public const REGISTRY_KEY = '/change_tracker/logs';

    private int $level;
    private \Ess\M2ePro\Model\Registry\Manager $registry;
    private array $tmpLogs = [];

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
        $this->log(\Monolog\Logger::EMERGENCY, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function alert($message, array $context = []): void
    {
        $this->log(\Monolog\Logger::ALERT, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function critical($message, array $context = []): void
    {
        $this->log(\Monolog\Logger::CRITICAL, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function error($message, array $context = []): void
    {
        $this->log(\Monolog\Logger::ERROR, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function warning($message, array $context = []): void
    {
        $this->log(\Monolog\Logger::WARNING, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function notice($message, array $context = []): void
    {
        $this->log(\Monolog\Logger::NOTICE, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function info($message, array $context = []): void
    {
        $this->log(\Monolog\Logger::INFO, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function debug($message, array $context = []): void
    {
        $this->log(\Monolog\Logger::DEBUG, $message, $context);
    }

    /**
     * @throws \Exception
     */
    public function log($level, $message, array $context = []): void
    {
        if ($this->level > $level) {
            return;
        }

        if (array_key_exists('tracker', $context)) {
            /** @var \Ess\M2ePro\Model\ChangeTracker\TrackerInterface $tracker */
            $tracker = $context['tracker'];
            unset($context['tracker']);

            $prefix = sprintf(
                '<b>%s</b> >> <b>%s</b> >> <b>[%s - %s]</b> >> ',
                strtoupper($tracker->getChannel()),
                strtoupper($tracker->getType()),
                $tracker->getListingProductIdFrom(),
                $tracker->getListingProductIdTo()
            );

            $message = $prefix . $message;
        }

        $this->tmpLogs[] = [
            'date' => \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s'),
            'level' => \Monolog\Logger::getLevelName($level),
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
