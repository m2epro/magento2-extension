<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Connection;

/**
 * Class \Ess\M2ePro\Model\Connector\Connection\Single
 */
class Single extends \Ess\M2ePro\Model\Connector\Connection\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Connector\Connection\Request $request */
    protected $request = null;

    /** @var \Ess\M2ePro\Model\Connector\Connection\Response $response */
    protected $response = null;

    protected $timeout = 300;

    //########################################

    protected function sendRequest()
    {
        $package = [
            'headers' => $this->getHeaders(),
            'data'    => $this->getBody(),
            'timeout' => $this->getTimeout()
        ];

        return $this->getHelper('Server\Request')->single(
            $package,
            $this->getServerBaseUrl(),
            $this->getServerHostName(),
            $this->isTryToResendOnError(),
            $this->isTryToSwitchEndpointOnError(),
            $this->isCanIgnoreMaintenance()
        );
    }

    protected function processRequestResult(array $result)
    {
        try {
            $this->response = $this->modelFactory->getObject('Connector_Connection_Response');
            $this->response->initFromRawResponse($result['body']);
            $this->response->setRequestTime($this->requestTime);
        } catch (\Ess\M2ePro\Model\Exception\Connection\InvalidResponse $exception) {
            $this->isTryToSwitchEndpointOnError() && $this->helperFactory->getObject('Server')->switchEndpoint();

            $this->getHelper('Module\Logger')->process($result, 'Invalid Response Format', false);
            throw new \Ess\M2ePro\Model\Exception\Connection($this->getConnectionErrorMessage(), $result);
        }

        if ($this->getResponse()->isServerInMaintenanceMode()) {
            $this->getHelper('Server_Maintenance')->processUnexpectedMaintenance();
        }

        if ($this->getResponse()->getMessages()->hasSystemErrorEntity()) {
            throw new \Ess\M2ePro\Model\Exception(
                $this->getHelper('Module\Translation')->__(
                    "Internal Server Error(s) [%error_message%]",
                    $this->getResponse()->getMessages()->getCombinedSystemErrorsString()
                ),
                [],
                0,
                !$this->getResponse()->isServerInMaintenanceMode()
            );
        }
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Connector\Connection\Request $request
     * @return $this
     */
    public function setRequest(\Ess\M2ePro\Model\Connector\Connection\Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Connector\Connection\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    // ----------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Connector\Connection\Response $response
     * @return $this
     */
    public function setResponse(\Ess\M2ePro\Model\Connector\Connection\Response $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Connector\Connection\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    //########################################

    /**
     * @param $value
     * @return $this
     */
    public function setTimeout($value)
    {
        $this->timeout = (int)$value;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    //########################################

    public function getHeaders()
    {
        $command = $this->getRequest()->getCommand();

        return [
            'M2EPRO-API-VERSION: '.self::API_VERSION,
            'M2EPRO-API-COMPONENT: '.$this->getRequest()->getComponent(),
            'M2EPRO-API-COMPONENT-VERSION: '.$this->getRequest()->getComponentVersion(),
            'M2EPRO-API-COMMAND: /'.$command[0] .'/'.$command[1].'/'.$command[2].'/'
        ];
    }

    public function getBody()
    {
        return [
            'api_version' => self::API_VERSION,
            'request'     => $this->getHelper('Data')->jsonEncode($this->getRequest()->getInfo()),
            'data'        => $this->getHelper('Data')->jsonEncode($this->getRequest()->getData()),
            'raw_data'    => $this->getRequest()->getRawData()
        ];
    }

    //########################################
}
