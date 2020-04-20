<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

/**
 * Class \Ess\M2ePro\Helper\Module\Logger
 */
class Logger extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $modelFactory;
    protected $moduleConfig;
    protected $logSystemFactory;
    protected $phpEnvironmentRequest;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Model\Log\SystemFactory $logSystemFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest
    ) {
        $this->modelFactory = $modelFactory;
        $this->moduleConfig = $moduleConfig;
        $this->logSystemFactory = $logSystemFactory;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function process($logData, $type = null, $sendToServer = true)
    {
        try {
            $info  = $this->getLogMessage($logData, $type);
            $info .= $this->getStackTraceInfo();
            $info .= $this->getCurrentUserActionInfo();

            $this->log($info, $type);

            if (!$sendToServer || !(bool)(int)$this->moduleConfig->getGroupValue('/debug/logging/', 'send_to_server')) {
                return;
            }

            $type = $type === null ? 'undefined' : $type;
            $info .= $this->getHelper('Module_Support_Form')->getSummaryInfo();

            $this->send($info, $type);
        } catch (\Exception $exceptionTemp) {
        }
    }

    //########################################

    private function log($info, $type)
    {
        /** @var \Ess\M2ePro\Model\Log\System $log */
        $log = $this->logSystemFactory->create();

        $log->setType($type === null ? 'Logging' : "{$type} Logging");
        $log->setDescription($info);

        $log->save();
    }

    private function getLogMessage($logData, $type)
    {
        !is_string($logData) && $logData = print_r($logData, true);

        $logData = '[DATE] '.date('Y-m-d H:i:s', (int)gmdate('U')).PHP_EOL.
            '[TYPE] '.$type.PHP_EOL.
            '[MESSAGE] '.$logData.PHP_EOL.
            str_repeat('#', 80).PHP_EOL.PHP_EOL;

        return $logData;
    }

    private function getStackTraceInfo()
    {
        $exception = new \Exception('');
        $stackTraceInfo = <<<TRACE
-------------------------------- STACK TRACE INFO --------------------------------
{$exception->getTraceAsString()}

TRACE;

        return $stackTraceInfo;
    }

    //########################################

    private function getCurrentUserActionInfo()
    {
        $server = print_r($this->phpEnvironmentRequest->getServer()->toArray(), true);
        $get = print_r($this->phpEnvironmentRequest->getQuery()->toArray(), true);
        $post = print_r($this->phpEnvironmentRequest->getPost()->toArray(), true);

        $actionInfo = <<<ACTION
-------------------------------- ACTION INFO -------------------------------------
SERVER: {$server}
GET: {$get}
POST: {$post}

ACTION;

        return $actionInfo;
    }

    //########################################

    private function send($logData, $type)
    {
        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector(
            'logger',
            'add',
            'entity',
            ['info' => $logData,
            'type' => $type]
        );
        $dispatcherObject->process($connectorObj);
    }

    //########################################
}
