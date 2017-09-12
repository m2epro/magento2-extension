<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Connection;

use \Ess\M2ePro\Model\Connector\Connection\Response\Message;
use \Ess\M2ePro\Model\Connector\Connection\Multiple\RequestContainer;

class Multiple extends \Ess\M2ePro\Model\Connector\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Connector\Connection\Multiple\RequestContainer[] $request */
    private $requestsContainers = [];

    /** @var \Ess\M2ePro\Model\Connector\Connection\Response[] $response */
    private $responses = [];

    protected $asynchronous = false;

    // ########################################

    protected function sendRequest()
    {
        $packages = array();

        foreach ($this->getRequestsContainers() as $key => $requestContainer) {

            $packages[$key] = array(
                'headers' => $this->getHeaders($requestContainer->getRequest()),
                'data'    => $this->getBody($requestContainer->getRequest()),
                'timeout' => $requestContainer->getTimeout()
            );
        }

        return $this->getHelper('Server\Request')->multiple(
            $packages,
            $this->getServerBaseUrl(),
            $this->getServerHostName(),
            $this->isTryToResendOnError(),
            $this->isTryToSwitchEndpointOnError(),
            $this->isAsynchronous()
        );
    }

    protected function processRequestResult(array $result)
    {
        $responseError = false;
        $successResponses = array();

        $connectionErrorMessage = 'The Action was not completed because connection with M2E Pro Server was not set.
        There are several possible reasons:  temporary connection problem – please wait and try again later;
        block of outgoing connection by firewall – please, ensure that connection to s1.m2epro.com and
        s2.m2epro.com, port 443 is allowed; CURL library is not installed or it does not support HTTPS Protocol –
        please, install/update CURL library on your server and ensure it supports HTTPS Protocol.
        More information you can find <a target="_blank" href="'.
            $this->getHelper('Module\Support')
            ->getKnowledgebaseArticleUrl('664870-issues-with-m2e-pro-server-connection')
        .'">here</a>';

        foreach ($result as $key => $response) {

            try {

                if ($response['body'] === false) {

                    throw new \Ess\M2ePro\Model\Exception\Connection(
                        $connectionErrorMessage,
                        [
                            'curl_error_number'  => $response['curl_error_number'],
                            'curl_error_message' => $response['curl_error_message'],
                            'curl_info'          => $response['curl_info']
                        ]
                    );
                }

                $responseObj = $this->modelFactory->getObject('Connector\Connection\Response');
                $responseObj->initFromRawResponse($response['body']);
                $responseObj->setRequestTime($this->requestTime);

                $this->responses[$key] = $responseObj;
                $successResponses[] = $responseObj;

            } catch (\Ess\M2ePro\Model\Exception\Connection\InvalidResponse $exception) {

                $responseError = true;
                $this->responses[$key] = $this->createFailedResponse($connectionErrorMessage);
                $this->getHelper('Module\Logger')->process($response, 'Invalid Response Format', false);

            } catch (\Exception $exception) {

                $responseError = true;
                $this->responses[$key] = $this->createFailedResponse($connectionErrorMessage);
                $this->getHelper('Module\Exception')->process($exception, false);
            }
        }

        if ($responseError) {
            $this->isTryToSwitchEndpointOnError() && $this->getHelper('Server')->switchEndpoint();
        }

        foreach ($successResponses as $response) {

            if ($response->getMessages()->hasSystemErrorEntity()) {

                $exception = new \Ess\M2ePro\Model\Exception($this->getHelper('Module\Translation')->__(
                    "Internal Server Error(s) [%error_message%]",
                    $response->getMessages()->getCombinedSystemErrorsString()
                ), array(), 0, !$response->isServerInMaintenanceMode());

                $this->getHelper('Module\Exception')->process($exception);
            }
        }
    }

    // ########################################

    private function createFailedResponse($errorMessage)
    {
        $messages = array(array(
            Message::CODE_KEY   => 0,
            Message::TEXT_KEY   => $errorMessage,
            Message::TYPE_KEY   => Message::TYPE_ERROR,
            Message::SENDER_KEY => Message::SENDER_SYSTEM
        ));

        $failedResponse = $this->modelFactory->getObject('Connector\Connection\Response');
        $failedResponse->initFromPreparedResponse(array(), $messages);
        $failedResponse->setRequestTime($this->requestTime);

        return $failedResponse;
    }

    // ########################################

    /**
     * @param $key
     * @param RequestContainer $requestContainer
     * @return $this
     */
    public function addRequestContainer($key, RequestContainer $requestContainer)
    {
        $this->requestsContainers[$key] = $requestContainer;
        return $this;
    }

    /**
     * @return RequestContainer[]
     */
    public function getRequestsContainers()
    {
        return $this->requestsContainers;
    }

    /**
     * @param $key
     * @return \Ess\M2ePro\Model\Connector\Connection\Request
     */
    public function getRequest($key)
    {
        return isset($this->requestsContainers[$key]) ? $this->requestsContainers[$key]->getRequest() : NULL;
    }

    // ----------------------------------------

    /**
     * @param $key
     * @return \Ess\M2ePro\Model\Connector\Connection\Response
     */
    public function getResponse($key)
    {
        return isset($this->responses[$key]) ? $this->responses[$key] : NULL;
    }

    /**
     * @return \Ess\M2ePro\Model\Connector\Connection\Response[]
     */
    public function getResponses()
    {
        return $this->responses;
    }

    // ----------------------------------------

    /**
     * @param $flag
     * @return $this
     */
    public function setAsynchronous($flag)
    {
        $this->asynchronous = (bool)$flag;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAsynchronous()
    {
        return $this->asynchronous;
    }

    // ########################################

    public function getHeaders(\Ess\M2ePro\Model\Connector\Connection\Request $request)
    {
        $command = $request->getCommand();

        return array(
            'M2EPRO-API-VERSION: '.self::API_VERSION,
            'M2EPRO-API-COMPONENT: '.$request->getComponent(),
            'M2EPRO-API-COMPONENT-VERSION: '.$request->getComponentVersion(),
            'M2EPRO-API-COMMAND: /'.$command[0] .'/'.$command[1].'/'.$command[2].'/'
        );
    }

    public function getBody(\Ess\M2ePro\Model\Connector\Connection\Request $request)
    {
        return array(
            'api_version' => self::API_VERSION,
            'request'     => $this->getHelper('Data')->jsonEncode($request->getInfo()),
            'data'        => $this->getHelper('Data')->jsonEncode($request->getData())
        );
    }

    // ########################################
}