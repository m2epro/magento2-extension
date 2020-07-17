<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing;

/**
 * Class \Ess\M2ePro\Model\Servicing\Dispatcher
 */
class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    const DEFAULT_INTERVAL = 3600;
    const MAX_MEMORY_LIMIT = 256;

    private $params = [];
    private $initiator;

    //########################################

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
    public function setParams(array $params = [])
    {
        $this->params = $params;
    }

    //########################################

    public function process($taskCodes = null)
    {
        $lastUpdate = $this->getLastUpdateDate();
        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));

        if ($this->getInitiator() !== \Ess\M2ePro\Helper\Data::INITIATOR_DEVELOPER &&
            $lastUpdate !== null &&
            $lastUpdate->getTimestamp() + self::DEFAULT_INTERVAL > $currentDate->getTimestamp()
        ) {
            return false;
        }

        $this->setLastUpdateDateTime();

        !is_array($taskCodes) && $taskCodes = $this->getRegisteredTasks();
        return $this->processTasks($taskCodes);
    }

    // ---------------------------------------

    public function processTask($taskCode)
    {
        return $this->processTasks([$taskCode]);
    }

    public function processTasks(array $taskCodes)
    {
        $this->getHelper('Client')->setMemoryLimit(self::MAX_MEMORY_LIMIT);
        $this->getHelper('Module\Exception')->setFatalErrorHandler();

        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getConnector(
            'server',
            'servicing',
            'updateData',
            $this->getRequestData($taskCodes)
        );

        $dispatcherObject->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        if (!is_array($responseData)) {
            return false;
        }

        $this->dispatchResponseData($responseData, $taskCodes);

        return true;
    }

    //########################################

    private function getRequestData(array $taskCodes)
    {
        $requestData = [];

        foreach ($this->getRegisteredTasks() as $taskName) {
            if (!in_array($taskName, $taskCodes)) {
                continue;
            }

            $taskModel = $this->getTaskModel($taskName);

            if (!$taskModel->isAllowed()) {
                continue;
            }

            $requestData[$taskModel->getPublicNick()] = $taskModel->getRequestData();
        }

        return $requestData;
    }

    private function dispatchResponseData(array $responseData, array $taskCodes)
    {
        foreach ($this->getRegisteredTasks() as $taskName) {
            if (!in_array($taskName, $taskCodes)) {
                continue;
            }

            $taskModel = $this->getTaskModel($taskName);

            if (!isset($responseData[$taskModel->getPublicNick()]) ||
                !is_array($responseData[$taskModel->getPublicNick()])) {
                continue;
            }

            $taskModel->processResponseData($responseData[$taskModel->getPublicNick()]);
        }
    }

    //########################################

    private function getTaskModel($taskName)
    {
        $taskName = preg_replace_callback('/_([a-z])/i', function ($matches) {
            return ucfirst($matches[1]);
        }, $taskName);

        /** @var $taskModel \Ess\M2ePro\Model\Servicing\Task */
        $taskModel = $this->modelFactory->getObject('Servicing\Task\\'.ucfirst($taskName));
        $taskModel->setParams($this->getParams());
        $taskModel->setInitiator($this->getInitiator());

        return $taskModel;
    }

    //########################################

    /**
     * @return array
     */
    public function getRegisteredTasks()
    {
        return [
            'license',
            'messages',
            'settings',
            'exceptions',
            'marketplaces',
            'cron',
            'statistic',
            'analytics',
            'maintenance_schedule',
            'product_variation_vocabulary'
        ];
    }

    /**
     * @return array
     */
    public function getSlowTasks()
    {
        return [
            'exceptions',
            'statistic',
            'analytics'
        ];
    }

    /**
     * @return array
     */
    public function getFastTasks()
    {
        return array_diff($this->getRegisteredTasks(), $this->getSlowTasks());
    }

    // ---------------------------------------

    private function getLastUpdateDate()
    {
        $lastUpdateDate = $this->getHelper('Module')->getRegistry()->getValue('/servicing/last_update_time/');

        if ($lastUpdateDate !== null) {
            $lastUpdateDate = new \DateTime($lastUpdateDate, new \DateTimeZone('UTC'));
        }

        return $lastUpdateDate;
    }

    private function setLastUpdateDateTime()
    {
        $this->getHelper('Module')->getRegistry()->setValue(
            '/servicing/last_update_time/',
            $this->getHelper('Data')->getCurrentGmtDate()
        );
    }

    //########################################
}
