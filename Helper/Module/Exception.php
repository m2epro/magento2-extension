<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Exception
{
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;
    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Helper\Module\Log */
    private $logHelper;
    /** @var string */
    private $systemLogTableName;
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    /** @var bool */
    private $isRegisterFatalHandler = false;

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\Module\Translation $translationHelper
     * @param \Ess\M2ePro\Helper\Module\Log $logHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Helper\Module\Log $logHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
        $this->translationHelper = $translationHelper;
        $this->logHelper = $logHelper;
        $this->objectManager = $objectManager;
    }

    /**
     * @param \Throwable $throwable
     *
     * @return void
     */
    public function process($throwable): void
    {
        try {
            $class = get_class($throwable);
            $info = $this->getExceptionDetailedInfo($throwable);

            $type = \Ess\M2ePro\Model\Log\System::TYPE_EXCEPTION;
            if ($throwable instanceof \Ess\M2ePro\Model\Exception\Connection) {
                $type = \Ess\M2ePro\Model\Log\System::TYPE_EXCEPTION_CONNECTOR;
            }

            $this->systemLog(
                $type,
                $class,
                $throwable->getMessage(),
                $info
            );
            // @codingStandardsIgnoreLine
        } catch (\Throwable $e) {
        }
    }

    /**
     * @param array $error
     * @param $traceInfo
     *
     * @return void
     */
    private function processFatal(array $error, $traceInfo): void
    {
        try {
            $class = 'Fatal Error';

            if (isset($error['message']) && strpos($error['message'], 'Allowed memory size') !== false) {
                $this->writeSystemLogByDirectSql(
                    300, // \Ess\M2ePro\Model\Log\System::TYPE_FATAL_ERROR
                    $class,
                    $error['message'],
                    $this->getFatalInfo($error, 'Fatal Error')
                );

                return;
            }

            $info = $this->getFatalErrorDetailedInfo($error, $traceInfo);

            $this->systemLog(
                \Ess\M2ePro\Model\Log\System::TYPE_FATAL_ERROR,
                $class,
                $error['message'],
                $info
            );
            // @codingStandardsIgnoreLine
        } catch (\Exception $exceptionTemp) {
        }
    }

    /**
     * @return void
     */
    public function setFatalErrorHandler(): void
    {
        if ($this->isRegisterFatalHandler) {
            return;
        }

        $this->isRegisterFatalHandler = true;

        $this->systemLogTableName = $this->objectManager->get(\Ess\M2ePro\Helper\Module\Database\Structure::class)
                                                        ->getTableNameWithPrefix(
                                                            'm2epro_system_log'
                                                        );

        $shutdownFunction = function () {
            $error = error_get_last();

            if ($error === null) {
                return;
            }

            $fatalErrors = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR];

            if (in_array((int)$error['type'], $fatalErrors)) {
                $trace = debug_backtrace(false);
                $traceInfo = $this->getFatalStackTraceInfo($trace);
                $this->processFatal($error, $traceInfo);
            }
        };

        // @codingStandardsIgnoreLine
        register_shutdown_function($shutdownFunction);
    }

    /**
     * @param \Throwable $exception
     *
     * @return string
     */
    public function getUserMessage(\Throwable $exception): string
    {
        return $this->translationHelper->__('Fatal error occurred') . ': "' . $exception->getMessage() . '".';
    }

    // ----------------------------------------

    /**
     * @param array $error
     * @param $traceInfo
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFatalErrorDetailedInfo(array $error, $traceInfo): string
    {
        $info = $this->getFatalInfo($error, 'Fatal Error');
        $info .= $traceInfo;
        $info .= $this->getAdditionalActionInfo();
        $info .= $this->logHelper->platformInfo();
        $info .= $this->logHelper->moduleInfo();

        return $info;
    }

    /**
     * @param \Throwable $throwable
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getExceptionDetailedInfo($throwable): string
    {
        $info = $this->getExceptionInfo($throwable, get_class($throwable));
        $info .= $this->getExceptionStackTraceInfo($throwable);
        $info .= $this->getAdditionalActionInfo();
        $info .= $this->logHelper->platformInfo();
        $info .= $this->logHelper->moduleInfo();

        return $info;
    }

    // ----------------------------------------

    /**
     * @param int $type
     * @param string $class
     * @param string $message
     * @param string $description
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function systemLog(int $type, string $class, string $message, string $description): void
    {
        // @codingStandardsIgnoreLine
        $trace = debug_backtrace();
        $file = $trace[1]['file'] ?? 'not set';
        $line = $trace[1]['line'] ?? 'not set';

        $additionalData = [
            'called-from' => $file . ' : ' . $line,
        ];

        /** @var \Ess\M2ePro\Model\Log\System $log */
        $log = $this->activeRecordFactory->getObject('Log\System');
        $log->setData(
            [
                'type'                 => $type,
                'class'                => $class,
                'description'          => $message,
                'detailed_description' => $description,
                // @codingStandardsIgnoreLine
                'additional_data'      => print_r($additionalData, true),
            ]
        );
        $log->save();
    }

    /**
     * @param int $type
     * @param string $class
     * @param string $message
     * @param string $description
     *
     * @return void
     * @throws \Exception
     */
    private function writeSystemLogByDirectSql(int $type, string $class, string $message, string $description): void
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->resourceConnection->getConnection()->insert(
            $this->systemLogTableName,
            [
                'type'                 => $type,
                'class'                => $class,
                'description'          => $message,
                'detailed_description' => $description,
                'create_date'          => $date->format('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * @param \Throwable $throwable
     * @param string $type
     *
     * @return string
     */
    private function getExceptionInfo($throwable, string $type): string
    {
        $additionalData = $throwable instanceof \Ess\M2ePro\Model\Exception ? $throwable->getAdditionalData() : '';
        // @codingStandardsIgnoreLine
        is_array($additionalData) && $additionalData = print_r($additionalData, true);

        return <<<EXCEPTION
-------------------------------- EXCEPTION INFO ----------------------------------
Type: {$type}
File: {$throwable->getFile()}
Line: {$throwable->getLine()}
Code: {$throwable->getCode()}
Message: {$throwable->getMessage()}
Additional Data: {$additionalData}

EXCEPTION;
    }

    /**
     * @param \Throwable $throwable
     *
     * @return string
     */
    private function getExceptionStackTraceInfo($throwable): string
    {
        return <<<TRACE
-------------------------------- STACK TRACE INFO --------------------------------
{$throwable->getTraceAsString()}

TRACE;
    }

    /**
     * @param array $error
     * @param string $type
     *
     * @return string
     */
    private function getFatalInfo(array $error, string $type): string
    {
        return <<<FATAL
-------------------------------- FATAL ERROR INFO --------------------------------
Type: {$type}
File: {$error['file']}
Line: {$error['line']}
Message: {$error['message']}

FATAL;
    }

    /**
     * @param array $stackTrace
     *
     * @return string
     */
    public function getFatalStackTraceInfo($stackTrace): string
    {
        if (!is_array($stackTrace)) {
            $stackTrace = [];
        }

        $stackTrace = array_reverse($stackTrace);
        $info = '';

        if (count($stackTrace) > 1) {
            foreach ($stackTrace as $key => $trace) {
                $info .= "#{$key} {$trace['file']}({$trace['line']}):";
                $info .= " {$trace['class']}{$trace['type']}{$trace['function']}(";

                if (!empty($trace['args'])) {
                    foreach ($trace['args'] as $argKey => $arg) {
                        $argKey !== 0 && $info .= ',';

                        if (is_object($arg)) {
                            $info .= get_class($arg);
                        } else {
                            $info .= $arg;
                        }
                    }
                }
                $info .= ")\n";
            }
        }

        if ($info === '') {
            $info = 'Unavailable';
        }

        return <<<TRACE
-------------------------------- STACK TRACE INFO --------------------------------
{$info}

TRACE;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getAdditionalActionInfo(): string
    {
        $currentStoreId = $this->storeManager->getStore()->getId();

        return <<<ACTION
-------------------------------- ADDITIONAL INFO -------------------------------------
Current Store: {$currentStoreId}

ACTION;
    }
}
