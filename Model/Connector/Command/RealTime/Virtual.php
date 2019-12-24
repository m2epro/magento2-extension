<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command\RealTime;

/**
 * Class \Ess\M2ePro\Model\Connector\Command\RealTime\Virtual
 */
class Virtual extends \Ess\M2ePro\Model\Connector\Command\RealTime
{
    // ########################################

    private $command = null;

    private $requestData = [];

    private $responseDataKey = null;

    private $requestTimeOut = null;

    // ########################################

    public function setCommand(array $command)
    {
        $this->command = $command;
        return $this;
    }

    public function setRequestData(array $requestData)
    {
        $this->requestData = $requestData;
        return $this;
    }

    public function setResponseDataKey($key)
    {
        $this->responseDataKey = $key;
        return $this;
    }

    public function setRequestTimeOut($value)
    {
        $this->requestTimeOut = $value;
        return $this;
    }

    // ########################################

    protected function getCommand()
    {
        if ($this->command === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Command was not set.');
        }

        return $this->command;
    }

    protected function getRequestData()
    {
        return $this->requestData;
    }

    // ########################################

    protected function getConnection()
    {
        if ($this->requestTimeOut === null) {
            return parent::getConnection();
        }

        $connection = parent::getConnection();
        $connection->setTimeout($this->requestTimeOut);

        return $connection;
    }

    // ########################################

    protected function prepareResponseData()
    {
        $responseData = $this->getResponse()->getResponseData();
        if ($this->responseDataKey === null) {
            $this->responseData = $responseData;
            return;
        }

        if (!isset($responseData[$this->responseDataKey])) {
            $this->responseData = $responseData;
            return;
        }

        $this->responseData = $responseData[$this->responseDataKey];
    }

    // ########################################
}
