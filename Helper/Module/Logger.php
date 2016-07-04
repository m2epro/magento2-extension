<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class Logger extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $modelFactory;
    protected $moduleConfig;
    protected $logSystemFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Model\Log\SystemFactory $logSystemFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->modelFactory = $modelFactory;
        $this->moduleConfig = $moduleConfig;
        $this->logSystemFactory = $logSystemFactory;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function process($logData, $type = NULL, $sendToServer = true)
    {
        try {

            $this->log($logData, $type);

            if (!$sendToServer || !(bool)(int)$this->moduleConfig->getGroupValue('/debug/logging/', 'send_to_server')) {
                return;
            }

            $type = is_null($type) ? 'undefined' : $type;

            $logData = $this->prepareLogMessage($logData, $type);
            $logData .= $this->getCurrentUserActionInfo();
            $logData .= $this->getHelper('Module\Support\Form');

            $this->send($logData, $type);

        } catch (\Exception $exceptionTemp) {}
    }

    //########################################

    private function prepareLogMessage($logData, $type)
    {
        !is_string($logData) && $logData = print_r($logData, true);

        $logData = '[DATE] '.date('Y-m-d H:i:s',(int)gmdate('U')).PHP_EOL.
                   '[TYPE] '.$type.PHP_EOL.
                   '[MESSAGE] '.$logData.PHP_EOL.
                   str_repeat('#',80).PHP_EOL.PHP_EOL;

        return $logData;
    }

    private function log($logData, $type)
    {
        /** @var \Ess\M2ePro\Model\Log\System $log */
        $log = $this->logSystemFactory->create();

        $log->setType(is_null($type) ? 'Logging' : "{$type} Logging");
        $log->setDescription(is_string($logData) ? $logData : print_r($logData, true));

        $log->save();
    }

    //########################################

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

    private function send($logData, $type)
    {
        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('logger', 'add', 'entity',
                                                               array('info' => $logData,
                                                                     'type' => $type));
        $dispatcherObject->process($connectorObj);
    }

    //########################################
}