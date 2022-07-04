<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Logger
{
    /** @var \Ess\M2ePro\Model\Log\SystemFactory */
    private $logSystemFactory;

    /**
     * @param \Ess\M2ePro\Model\Log\SystemFactory $logSystemFactory
     */
    public function __construct(
        \Ess\M2ePro\Model\Log\SystemFactory $logSystemFactory
    ) {
        $this->logSystemFactory = $logSystemFactory;
    }

    /**
     * @param mixed $logData
     * @param string $class
     *
     * @return void
     */
    public function process($logData, string $class = 'undefined'): void
    {
        try {
            $info = $this->getLogMessage($logData, $class);
            $info .= $this->getStackTraceInfo();

            $this->systemLog($class, null, $info);
        } catch (\Exception $exceptionTemp) {
        }
    }

    /**
     * @param string $class
     * @param string|null $message
     * @param string $description
     *
     * @return void
     */
    private function systemLog(string $class, ?string $message, string $description): void
    {
        $log = $this->logSystemFactory->create();
        $log->setData(
            [
                'type'                 => \Ess\M2ePro\Model\Log\System::TYPE_LOGGER,
                'class'                => $class,
                'description'          => $message,
                'detailed_description' => $description,
            ]
        );
        $log->save();
    }

    /**
     * @param mixed $logData
     * @param string $type
     *
     * @return string
     */
    private function getLogMessage($logData, string $type): string
    {
        // @codingStandardsIgnoreLine
        !is_string($logData) && $logData = print_r($logData, true);

        // @codingStandardsIgnoreLine
        return '[DATE] ' . date('Y-m-d H:i:s', (int)gmdate('U')) . PHP_EOL .
            '[TYPE] ' . $type . PHP_EOL .
            '[MESSAGE] ' . $logData . PHP_EOL .
            str_repeat('#', 80) . PHP_EOL . PHP_EOL;
    }

    /**
     * @return string
     */
    private function getStackTraceInfo(): string
    {
        $exception = new \Exception('');

        return <<<TRACE
-------------------------------- STACK TRACE INFO --------------------------------
{$exception->getTraceAsString()}

TRACE;
    }
}
