<?php

namespace Ess\M2ePro\Model\Connector\Connection;

abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    public const API_VERSION = 1;

    /** @var null  */
    protected $requestTime = null;
    /** @var null  */
    protected $host = null;
    /** @var bool  */
    protected $canIgnoreMaintenance = false;

    //########################################

    public function process()
    {
        try {
            $this->requestTime = $this->getHelper('Data')->getCurrentGmtDate();

            $result = $this->sendRequest();
        } catch (\Exception $exception) {
            $this->getHelper('Client')->updateMySqlConnection();
            throw $exception;
        }

        $this->getHelper('Client')->updateMySqlConnection();

        $this->processRequestResult($result);
    }

    // ----------------------------------------

    abstract protected function sendRequest();

    abstract protected function processRequestResult(array $result);

    //########################################

    public function setHost($value)
    {
        $this->host = $value;

        return $this;
    }

    public function getHost()
    {
        return $this->host;
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isCanIgnoreMaintenance()
    {
        return $this->canIgnoreMaintenance;
    }

    /**
     * @param bool $canIgnoreMaintenance
     */
    public function setCanIgnoreMaintenance($canIgnoreMaintenance)
    {
        $this->canIgnoreMaintenance = $canIgnoreMaintenance;
    }

    protected function getConnectionErrorMessage()
    {
        return (string) __(
            'M2E Pro Server connection failed. Find the solution <a target="_blank" href="%1">here</a>',
            'https://help.m2epro.com/support/solutions/articles/9000200887'
        );
    }

    //########################################
}
