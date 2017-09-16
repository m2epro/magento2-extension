<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    const DEFAULT_INTERVAL = 3600;
    const MAX_MEMORY_LIMIT = 256;

    private $params = array();
    private $forceTasksRunning = false;
    private $initiator;

    protected $cacheConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->cacheConfig = $cacheConfig;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function getForceTasksRunning()
    {
        return $this->forceTasksRunning;
    }

    public function setForceTasksRunning($value)
    {
        $this->forceTasksRunning = (bool)$value;
    }

    // ---------------------------------------

    public function setInitiator($initiator)
    {
        $this->initiator = $initiator;
        return $this;
    }

    public function getInitiator()
    {
        return $this->initiator;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params = array())
    {
        $this->params = $params;
    }

    //########################################

    public function process($minInterval = NULL, $taskCodes = NULL)
    {
        $timeLastUpdate = $this->getLastUpdateTimestamp();

        if ($this->getInitiator() !== \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER &&
            !is_null($minInterval) &&
            $timeLastUpdate + (int)$minInterval > $this->getHelper('Data')->getCurrentGmtDate(true)) {
            return false;
        }

        $this->setLastUpdateDateTime();

        !is_array($taskCodes) && $taskCodes = $this->getRegisteredTasks();
        return $this->processTasks($taskCodes);
    }

    // ---------------------------------------

    public function processTask($taskCode)
    {
        return $this->processTasks(array($taskCode));
    }

    public function processTasks(array $taskCodes)
    {
        $this->getHelper('Client')->setMemoryLimit(self::MAX_MEMORY_LIMIT);
        $this->getHelper('Module\Exception')->setFatalErrorHandler();

        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('servicing','update','data',
                                                               $this->getRequestData($taskCodes));

        $dispatcherObject->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        if (!is_array($responseData)) {
            return false;
        }

        $this->dispatchResponseData($responseData,$taskCodes);

        return true;
    }

    //########################################

    private function getRequestData(array $taskCodes)
    {
        $requestData = array();

        foreach ($this->getRegisteredTasks() as $taskName) {

            if (!in_array($taskName,$taskCodes)) {
                continue;
            }

            /** @var $taskModel \Ess\M2ePro\Model\Servicing\Task */
            $taskModel = $this->modelFactory->getObject('Servicing\Task\\'.ucfirst($taskName));
            $taskModel->setParams($this->getParams());
            $taskModel->setInitiator($this->getInitiator());

            if (!$this->getForceTasksRunning() && !$taskModel->isAllowed()) {
                continue;
            }

            $requestData[$taskModel->getPublicNick()] = $taskModel->getRequestData();
        }

        return $requestData;
    }

    private function dispatchResponseData(array $responseData, array $taskCodes)
    {
        foreach ($this->getRegisteredTasks() as $taskName) {

            if (!in_array($taskName,$taskCodes)) {
                continue;
            }

            /** @var $taskModel \Ess\M2ePro\Model\Servicing\Task */
            $taskModel = $this->modelFactory->getObject('Servicing\Task\\'.ucfirst($taskName));
            $taskModel->setParams($this->getParams());

            if (!isset($responseData[$taskModel->getPublicNick()]) ||
                !is_array($responseData[$taskModel->getPublicNick()])) {
                continue;
            }

            $taskModel->processResponseData($responseData[$taskModel->getPublicNick()]);
        }
    }

    //########################################

    /**
     * @return array
     */
    public function getRegisteredTasks()
    {
        return array(
            'license',
            'messages',
            'settings',
            'exceptions',
            'marketplaces',
            'cron',
            'statistic'
        );
    }

    /**
     * @return array
     */
    public function getSlowTasks()
    {
        return array(
            'exceptions',
            'statistic'
        );
    }

    /**
     * @return array
     */
    public function getFastTasks()
    {
        return array_diff($this->getRegisteredTasks(), $this->getSlowTasks());
    }

    // ---------------------------------------

    private function getLastUpdateTimestamp()
    {
        $lastUpdateDate = $this->cacheConfig->getGroupValue('/servicing/','last_update_time');

        if (is_null($lastUpdateDate)) {
            return $this->getHelper('Data')->getCurrentGmtDate(true) - 3600*24*30;
        }

        return $this->getHelper('Data')->getDate($lastUpdateDate,true);
    }

    private function setLastUpdateDateTime()
    {
        $this->cacheConfig
            ->setGroupValue('/servicing/', 'last_update_time',
                            $this->getHelper('Data')->getCurrentGmtDate());
    }

    //########################################
}