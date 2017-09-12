<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Connection;

class Single extends \Ess\M2ePro\Model\Connector\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Connector\Connection\Request $request */
    private $request = NULL;

    /** @var \Ess\M2ePro\Model\Connector\Connection\Response $response */
    private $response = NULL;

    private $timeout = 300;

    // ########################################

    protected function sendRequest()
    {
        $package = array(
            'headers' => $this->getHeaders(),
            'data'    => $this->getBody(),
            'timeout' => $this->getTimeout()
        );

        return $this->getHelper('Server\Request')->single(
            $package,
            $this->getServerBaseUrl(),
            $this->getServerHostName(),
            $this->isTryToResendOnError(),
            $this->isTryToSwitchEndpointOnError()
        );
    }

    protected function processRequestResult(array $result)
    {
        try {

            $this->response = $this->modelFactory->getObject('Connector\Connection\Response');
            $this->response->initFromRawResponse($result['body']);
            $this->response->setRequestTime($this->requestTime);

        } catch (\Ess\M2ePro\Model\Exception\Connection\InvalidResponse $exception) {

            $this->isTryToSwitchEndpointOnError() && $this->helperFactory->getObject('Server')->switchEndpoint();

            $this->getHelper('Module\Logger')->process($result, 'Invalid Response Format', false);

            $connectionErrorMessage = 'The Action was not completed because connection with M2E Pro Server was not set.
            There are several possible reasons:  temporary connection problem – please wait and try again later;
            block of outgoing connection by firewall – please, ensure that connection to s1.m2epro.com and
            s2.m2epro.com, port 443 is allowed; CURL library is not installed or it does not support HTTPS Protocol –
            please, install/update CURL library on your server and ensure it supports HTTPS Protocol.
            More information you can find <a target="_blank" href="'.
                $this->helperFactory->getObject('Module\Support')
                    ->getKnowledgebaseArticleUrl('664870-issues-with-m2e-pro-server-connection')
                .'">here</a>';

            throw new \Ess\M2ePro\Model\Exception\Connection($connectionErrorMessage, $result);
        }

        if ($this->getResponse()->getMessages()->hasSystemErrorEntity()) {

            throw new \Ess\M2ePro\Model\Exception($this->getHelper('Module\Translation')->__(
                "Internal Server Error(s) [%error_message%]",
                $this->getResponse()->getMessages()->getCombinedSystemErrorsString()
            ), array(), 0, !$this->getResponse()->isServerInMaintenanceMode());
        }
    }

    // ########################################

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

    // ########################################

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

    // ########################################

    public function getHeaders()
    {
        $command = $this->getRequest()->getCommand();

        return array(
            'M2EPRO-API-VERSION: '.self::API_VERSION,
            'M2EPRO-API-COMPONENT: '.$this->getRequest()->getComponent(),
            'M2EPRO-API-COMPONENT-VERSION: '.$this->getRequest()->getComponentVersion(),
            'M2EPRO-API-COMMAND: /'.$command[0] .'/'.$command[1].'/'.$command[2].'/'
        );
    }

    public function getBody()
    {
        return array(
            'api_version' => self::API_VERSION,
            'request'     => $this->getHelper('Data')->jsonEncode($this->getRequest()->getInfo()),
            'data'        => $this->getHelper('Data')->jsonEncode($this->getRequest()->getData())
        );
    }

    // ########################################
}