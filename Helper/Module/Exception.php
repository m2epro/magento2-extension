<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Exception extends \Ess\M2ePro\Helper\AbstractHelper
{
    const FILTER_TYPE_TYPE    = 1;
    const FILTER_TYPE_INFO    = 2;
    const FILTER_TYPE_MESSAGE = 3;

    private $activeRecordFactory;
    private $modelFactory;
    private $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->modelFactory = $modelFactory;
        $this->moduleConfig = $moduleConfig;
        parent::__construct($helperFactory, $context);
    }
    
    //########################################

    public function process(\Exception $exception, $sendToServer = true)
    {
        try {

            $type = get_class($exception);

            $info = $this->getExceptionInfo($exception, $type);
            $info .= $this->getExceptionStackTraceInfo($exception);
            $info .= $this->getCurrentUserActionInfo();
            $info .= $this->getHelper('Module\Support\Form')->getSummaryInfo();

            $this->log($info, $type);

            if (!$sendToServer ||
                ($exception instanceof \Ess\M2ePro\Model\Exception && !$exception->isSendToServer()) ||
                !(bool)(int)$this->moduleConfig->getGroupValue('/debug/exceptions/','send_to_server') ||
                $this->isExceptionFiltered($info, $exception->getMessage(), $type)) {
                return;
            }

            $temp = $this->getHelper('Data\GlobalData')->getValue('send_exception_to_server');
            if (!empty($temp)) {
                return;
            }
            $this->getHelper('Data\GlobalData')->setValue('send_exception_to_server', true);

            $this->send($info, $exception->getMessage(), $type);

            $this->getHelper('Data\GlobalData')->unsetValue('send_exception_to_server');

        } catch (\Exception $exceptionTemp) {}
    }

    public function processFatal($error, $traceInfo)
    {
        try {

            $type = 'Fatal Error';

            $info = $this->getFatalInfo($error, $type);
            $info .= $traceInfo;
            $info .= $this->getCurrentUserActionInfo();
            $info .= $this->getHelper('Module\Support\Form')->getSummaryInfo();

            $this->log($info, $type);

            if (!(bool)(int)$this->moduleConfig->getGroupValue('/debug/fatal_error/','send_to_server') ||
                $this->isExceptionFiltered($info, $error['message'], $type)) {
                return;
            }

            $temp = $this->getHelper('Data\GlobalData')->getValue('send_exception_to_server');
            if (!empty($temp)) {
                return;
            }
            $this->getHelper('Data\GlobalData')->setValue('send_exception_to_server', true);

            $this->send($info, $error['message'], $type);

            $this->getHelper('Data\GlobalData')->unsetValue('send_exception_to_server');

        } catch (\Exception $exceptionTemp) {}
    }

    // ---------------------------------------

    public function setFatalErrorHandler()
    {
        $temp = $this->getHelper('Data\GlobalData')->getValue('set_fatal_error_handler');

        if (!empty($temp)) {
            return;
        }

        $this->getHelper('Data\GlobalData')->setValue('set_fatal_error_handler', true);

        $exceptionHelper = $this->getHelper('Module\Exception');
        $shutdownFunction = function() use($exceptionHelper) {
            $error = error_get_last();

            if (is_null($error)) {
                return;
            }

            $fatalErrors = array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR);

            if (in_array((int)$error['type'], $fatalErrors)) {
                $trace = @debug_backtrace(false);
                $traceInfo = $exceptionHelper->getFatalStackTraceInfo($trace);
                $exceptionHelper->processFatal($error,$traceInfo);
            }
        };

        register_shutdown_function($shutdownFunction);
    }

    public function getUserMessage(\Exception $exception)
    {
        return $this->getHelper('Module\Translation')->__('Fatal error occurred').': "'.$exception->getMessage().'".';
    }

    //########################################

    private function log($message, $type)
    {
        /** @var \Ess\M2ePro\Model\Log\System $log */
        $log = $this->activeRecordFactory->getObject('Log\System');

        $log->setType($type);
        $log->setDescription($message);

        $log->save();
    }

    //########################################

    private function getExceptionInfo(\Exception $exception, $type)
    {
        $additionalData = $exception instanceof \Ess\M2ePro\Model\Exception ? $exception->getAdditionalData()
                                                                           : '';

        is_array($additionalData) && $additionalData = print_r($additionalData, true);

        $exceptionInfo = <<<EXCEPTION
-------------------------------- EXCEPTION INFO ----------------------------------
Type: {$type}
File: {$exception->getFile()}
Line: {$exception->getLine()}
Code: {$exception->getCode()}
Message: {$exception->getMessage()}
Additional Data: {$additionalData}

EXCEPTION;

        return $exceptionInfo;
    }

    private function getExceptionStackTraceInfo(\Exception $exception)
    {
        $stackTraceInfo = <<<TRACE
-------------------------------- STACK TRACE INFO --------------------------------
{$exception->getTraceAsString()}

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
            $stackTrace = array();
        }

        $stackTrace = array_reverse($stackTrace);
        $info = '';

        if (count($stackTrace) > 1) {
            foreach ($stackTrace as $key => $trace) {
                $info .= "#{$key} {$trace['file']}({$trace['line']}):";
                $info .= " {$trace['class']}{$trace['type']}{$trace['function']}(";

                if (count($trace['args'])) {
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
        $server = isset($_SERVER) ? print_r($_SERVER, true) : '';
        $get = isset($_GET) ? print_r($_GET, true) : '';
        $post = isset($_POST) ? print_r($_POST, true) : '';

        $actionInfo = <<<ACTION
-------------------------------- ACTION INFO -------------------------------------
SERVER: {$server}
GET: {$get}
POST: {$post}

ACTION;

        return $actionInfo;
    }

    //########################################

    private function send($info, $message, $type)
    {
        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('exception','add','entity',
                                                               array('info'    => $info,
                                                                     'message' => $message,
                                                                     'type'    => $type));

        $dispatcherObject->process($connectorObj);
    }

    private function isExceptionFiltered($info, $message, $type)
    {
        if (!(bool)(int)$this->moduleConfig->getGroupValue('/debug/exceptions/','filters_mode')) {
            return false;
        }

        $registry = $this->activeRecordFactory->getObjectLoaded(
            'Registry', '/exceptions_filters/', 'key', false
        );

        $exceptionFilters = [];
        if (!is_null($registry)) {
            $exceptionFilters = $registry->getValueFromJson();
        }

        foreach ($exceptionFilters as $exceptionFilter) {

            try {

                $searchSubject = '';
                $exceptionFilter['type'] == self::FILTER_TYPE_TYPE    && $searchSubject = $type;
                $exceptionFilter['type'] == self::FILTER_TYPE_MESSAGE && $searchSubject = $message;
                $exceptionFilter['type'] == self::FILTER_TYPE_INFO    && $searchSubject = $info;

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