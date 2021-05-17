<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

/**
 * Class \Ess\M2ePro\Helper\Module\Exception
 */
class Exception extends \Ess\M2ePro\Helper\AbstractHelper
{
    const FILTER_TYPE_TYPE    = 1;
    const FILTER_TYPE_INFO    = 2;
    const FILTER_TYPE_MESSAGE = 3;

    private $activeRecordFactory;
    private $modelFactory;
    private $phpEnvironmentRequest;
    private $storeManager;

    protected $systemLogTableName;
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->activeRecordFactory   = $activeRecordFactory;
        $this->modelFactory          = $modelFactory;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        $this->storeManager          = $storeManager;
        $this->resourceConnection    = $resourceConnection;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function process($throwable, $sendToServer = true)
    {
        /**@var \Exception $throwable */

        try {
            $class = get_class($throwable);
            $info  = $this->getExceptionDetailedInfo($throwable);

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

            $sendConfig = (bool)(int)$this->getHelper('Module')->getConfig()
                ->getGroupValue('/server/exceptions/', 'send');

            if (!$sendToServer ||
                ($throwable instanceof \Ess\M2ePro\Model\Exception && !$throwable->isSendToServer()) ||
                !$sendConfig || $this->isExceptionFiltered($info, $throwable->getMessage(), $class)
            ) {
                return;
            }

            $temp = $this->getHelper('Data\GlobalData')->getValue('send_exception_to_server');
            if (!empty($temp)) {
                return;
            }

            $this->getHelper('Data\GlobalData')->setValue('send_exception_to_server', true);

            $this->send($info, $throwable->getMessage(), $class);

            $this->getHelper('Data\GlobalData')->unsetValue('send_exception_to_server');

            // @codingStandardsIgnoreLine
        } catch (\Exception $exceptionTemp) {
        }
    }

    public function processFatal($error, $traceInfo)
    {
        try {
            $class = 'Fatal Error';

            if (isset($error['message']) && strpos($error['message'], 'Allowed memory size') !== false) {
                $this->writeSystemLogByDirectSql(
                    300, //\Ess\M2ePro\Model\Log\System::TYPE_FATAL_ERROR
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

            $sendToServer = (bool)(int)$this->getHelper('Module')->getConfig()
                ->getGroupValue('/server/fatal_error/', 'send');

            if (!$sendToServer || $this->isExceptionFiltered($info, $error['message'], $class)) {
                return;
            }

            $temp = $this->getHelper('Data\GlobalData')->getValue('send_exception_to_server');
            if (!empty($temp)) {
                return;
            }

            $this->getHelper('Data\GlobalData')->setValue('send_exception_to_server', true);

            $this->send($info, $error['message'], $class);

            $this->getHelper('Data\GlobalData')->unsetValue('send_exception_to_server');

            // @codingStandardsIgnoreLine
        } catch (\Exception $exceptionTemp) {
        }
    }

    // ---------------------------------------

    public function setFatalErrorHandler()
    {
        $temp = $this->getHelper('Data\GlobalData')->getValue('set_fatal_error_handler');

        if (!empty($temp)) {
            return;
        }

        $this->getHelper('Data\GlobalData')->setValue('set_fatal_error_handler', true);

        $this->systemLogTableName = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix(
            'm2epro_system_log'
        );

        $exceptionHelper  = $this->getHelper('Module\Exception');
        $shutdownFunction = function () use ($exceptionHelper) {
            $error = error_get_last();

            if ($error === null) {
                return;
            }

            $fatalErrors = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR];

            if (in_array((int)$error['type'], $fatalErrors)) {
                $trace     = debug_backtrace(false);
                $traceInfo = $exceptionHelper->getFatalStackTraceInfo($trace);
                $exceptionHelper->processFatal($error, $traceInfo);
            }
        };

        // @codingStandardsIgnoreLine
        register_shutdown_function($shutdownFunction);
    }

    public function getUserMessage(\Exception $exception)
    {
        return $this->getHelper('Module\Translation')->__('Fatal error occurred').': "'.$exception->getMessage().'".';
    }

    //########################################

    public function getFatalErrorDetailedInfo($error, $traceInfo)
    {
        $info = $this->getFatalInfo($error, 'Fatal Error');
        $info .= $traceInfo;
        $info .= $this->getCurrentUserActionInfo();
        $info .= $this->getAdditionalActionInfo();
        $info .= $this->getHelper('Module_Support_Form')->getSummaryInfo();

        return $info;
    }

    public function getExceptionDetailedInfo($throwable)
    {
        /**@var \Exception $throwable */

        $info = $this->getExceptionInfo($throwable, get_class($throwable));
        $info .= $this->getExceptionStackTraceInfo($throwable);
        $info .= $this->getCurrentUserActionInfo();
        $info .= $this->getAdditionalActionInfo();
        $info .= $this->getHelper('Module_Support_Form')->getSummaryInfo();

        return $info;
    }

    //########################################

    protected function systemLog($type, $class, $message, $description)
    {
        // @codingStandardsIgnoreLine
        $trace = debug_backtrace();
        $file  = isset($trace[1]['file']) ? $trace[1]['file'] : 'not set';
        $line  = isset($trace[1]['line']) ? $trace[1]['line'] : 'not set';

        $additionalData = [
            'called-from' => $file . ' : ' . $line
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

    private function writeSystemLogByDirectSql($type, $class, $message, $description)
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->resourceConnection->getConnection()->insert(
            $this->systemLogTableName,
            [
                'type'                 => $type,
                'class'                => $class,
                'description'          => $message,
                'detailed_description' => $description,
                'create_date'          => $date->format('Y-m-d H:i:s')
            ]
        );
    }

    //########################################

    private function getExceptionInfo($throwable, $type)
    {
        /**@var \Exception $throwable */

        $additionalData = $throwable instanceof \Ess\M2ePro\Model\Exception ? $throwable->getAdditionalData()
            : '';
        // @codingStandardsIgnoreLine
        is_array($additionalData) && $additionalData = print_r($additionalData, true);

        $exceptionInfo = <<<EXCEPTION
-------------------------------- EXCEPTION INFO ----------------------------------
Type: {$type}
File: {$throwable->getFile()}
Line: {$throwable->getLine()}
Code: {$throwable->getCode()}
Message: {$throwable->getMessage()}
Additional Data: {$additionalData}

EXCEPTION;

        return $exceptionInfo;
    }

    private function getExceptionStackTraceInfo($throwable)
    {
        /**@var \Exception $throwable */

        $stackTraceInfo = <<<TRACE
-------------------------------- STACK TRACE INFO --------------------------------
{$throwable->getTraceAsString()}

TRACE;

        return $stackTraceInfo;
    }

    // ---------------------------------------

    private function getFatalInfo($error, $type)
    {
        $fatalInfo = <<<FATAL
-------------------------------- FATAL ERROR INFO --------------------------------
Type: {$type}
File: {$error['file']}
Line: {$error['line']}
Message: {$error['message']}

FATAL;

        return $fatalInfo;
    }

    public function getFatalStackTraceInfo($stackTrace)
    {
        if (!is_array($stackTrace)) {
            $stackTrace = [];
        }

        $stackTrace = array_reverse($stackTrace);
        $info       = '';

        if (count($stackTrace) > 1) {
            foreach ($stackTrace as $key => $trace) {
                $info .= "#{$key} {$trace['file']}({$trace['line']}):";
                $info .= " {$trace['class']}{$trace['type']}{$trace['function']}(";

                if (!empty($trace['args'])) {
                    foreach ($trace['args'] as $key => $arg) {
                        $key != 0 && $info .= ',';

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

        if ($info == '') {
            $info = 'Unavailable';
        }

        $stackTraceInfo = <<<TRACE
-------------------------------- STACK TRACE INFO --------------------------------
{$info}

TRACE;

        return $stackTraceInfo;
    }

    // ---------------------------------------

    private function getCurrentUserActionInfo()
    {
        // @codingStandardsIgnoreStart
        $server = print_r($this->phpEnvironmentRequest->getServer()->toArray(), true);
        $get    = print_r($this->phpEnvironmentRequest->getQuery()->toArray(), true);
        $post   = print_r($this->phpEnvironmentRequest->getPost()->toArray(), true);
        // @codingStandardsIgnoreEnd

        $actionInfo = <<<ACTION
-------------------------------- ACTION INFO -------------------------------------
SERVER: {$server}
GET: {$get}
POST: {$post}

ACTION;

        return $actionInfo;
    }

    private function getAdditionalActionInfo()
    {
        $currentStoreId = $this->storeManager->getStore()->getId();

        $actionInfo = <<<ACTION
-------------------------------- ADDITIONAL INFO -------------------------------------
Current Store: {$currentStoreId}

ACTION;

        return $actionInfo;
    }

    //########################################

    private function send($info, $message, $type)
    {
        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj     = $dispatcherObject->getVirtualConnector(
            'exception',
            'add',
            'entity',
            [
                'info'    => $info,
                'message' => $message,
                'type'    => $type
            ]
        );

        $dispatcherObject->process($connectorObj);
    }

    private function isExceptionFiltered($info, $message, $type)
    {
        if (!(bool)(int)$this->getHelper('Module')->getConfig()->getGroupValue('/server/exceptions/', 'filters')) {
            return false;
        }

        $exceptionFilters = $this->getHelper('Module')->getRegistry()->getValueFromJson('/exceptions_filters/');

        foreach ($exceptionFilters as $exceptionFilter) {
            try {
                $searchSubject = '';
                $exceptionFilter['type'] == self::FILTER_TYPE_TYPE && $searchSubject = $type;
                $exceptionFilter['type'] == self::FILTER_TYPE_MESSAGE && $searchSubject = $message;
                $exceptionFilter['type'] == self::FILTER_TYPE_INFO && $searchSubject = $info;

                $tempResult = preg_match($exceptionFilter['preg_match'], $searchSubject);
            } catch (\Exception $exception) {
                return false;
            }

            if ($tempResult) {
                return true;
            }
        }

        return false;
    }

    //########################################
}
