<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command;

abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    // ########################################

    protected $params = array();

    /** @var \Ess\M2ePro\Model\Connector\Protocol */
    protected $protocol = NULL;

    /** @var \Ess\M2ePro\Model\Connector\Connection\Single $connection */
    protected $connection = NULL;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    )
    {
        $this->params = $params;
        parent::__construct($helperFactory, $modelFactory);
    }

    // ########################################

    public function setProtocol(\Ess\M2ePro\Model\Connector\Protocol $protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }

    public function getProtocol()
    {
        return $this->protocol;
    }

    // ########################################

    abstract public function process();

    // ########################################

    public function getRequestDataPackage()
    {
        return $this->getRequest()->getPackage();
    }

    // ########################################

    protected function getConnection()
    {
        if (!is_null($this->connection)) {
            return $this->connection;
        }

        return $this->connection = $this->buildConnectionInstance();
    }

    protected function buildConnectionInstance()
    {
        $connection = $this->modelFactory->getObject('Connector\Connection\Single');
        $connection->setRequest($this->buildRequestInstance());

        return $connection;
    }

    // ----------------------------------------

    protected function buildRequestInstance()
    {
        $request = $this->modelFactory->getObject('Connector\Connection\Request');
        $request->setCommand($this->getCommand());

        $request->setComponent($this->getProtocol()->getComponent());
        $request->setComponentVersion($this->getProtocol()->getComponentVersion());

        $request->setData($this->getRequestData());

        return $request;
    }

    // ########################################

    public function getRequest()
    {
        return $this->getConnection()->getRequest();
    }

    public function getResponse()
    {
        return $this->getConnection()->getResponse();
    }

    // ########################################

    public function getRequestTime()
    {
        return $this->getResponse()->getRequestTime();
    }

    // ########################################

    /**
     * @return array
     */
    abstract protected function getRequestData();

    /**
     * @return array
     */
    abstract protected function getCommand();

    // ########################################
}