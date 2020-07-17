<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Connection;

use \Ess\M2ePro\Model\Connector\Connection\Response\Message;
use \Ess\M2ePro\Model\Connector\Connection\Multiple\RequestContainer;

/**
 * Class \Ess\M2ePro\Model\Connector\Connection\Multiple
 */
class Multiple extends \Ess\M2ePro\Model\Connector\Connection\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Connector\Connection\Multiple\RequestContainer[] $request */
    protected $requestsContainers = [];

    /** @var \Ess\M2ePro\Model\Connector\Connection\Response[] $response */
    protected $responses = [];

    protected $asynchronous = false;

    //########################################

    protected function sendRequest()
    {
        $packages = [];

        foreach ($this->getRequestsContainers() as $key => $requestContainer) {
            $packages[$key] = [
                'headers' => $this->getHeaders($requestContainer->getRequest()),
                'data'    => $this->getBody($requestContainer->getRequest()),
                'timeout' => $requestContainer->getTimeout()
            ];
        }

        return $this->getHelper('Server\Request')->multiple(
            $packages,
            $this->getServerBaseUrl(),
            $this->getServerHostName(),
            $this->isTryToResendOnError(),
            $this->isTryToSwitchEndpointOnError(),
            $this->isAsynchronous(),
            $this->isCanIgnoreMaintenance()
        );
    }

    protected function processRequestResult(array $result)
    {
        $responseError = false;
        $successResponses = [];

        foreach ($result as $key => $response) {
            try {
                if ($response['body'] === false) {
                    throw new \Ess\M2ePro\Model\Exception\Connection(
                        $this->getConnectionErrorMessage(),
                        [
                            'curl_error_number'  => $response['curl_error_number'],
                            'curl_error_message' => $response['curl_error_message'],
                            'curl_info'          => $response['curl_info']
                        ]
                    );
                }

                $responseObj = $this->modelFactory->getObject('Connector_Connection_Response');
                $responseObj->initFromRawResponse($response['body']);
                $responseObj->setRequestTime($this->requestTime);

                $this->responses[$key] = $responseObj;
                $successResponses[] = $responseObj;
            } catch (\Ess\M2ePro\Model\Exception\Connection\InvalidResponse $exception) {
                $responseError = true;
                $this->responses[$key] = $this->createFailedResponse($this->getConnectionErrorMessage());
                $this->getHelper('Module\Logger')->process($response, 'Invalid Response Format', false);
            } catch (\Exception $exception) {
                $responseError = true;
                $this->responses[$key] = $this->createFailedResponse($this->getConnectionErrorMessage());
                $this->getHelper('Module\Exception')->process($exception, false);
            }
        }

        if ($responseError) {
            $this->isTryToSwitchEndpointOnError() && $this->getHelper('Server')->switchEndpoint();
        }

        foreach ($successResponses as $response) {
            if ($response->isServerInMaintenanceMode()) {
                $this->getHelper('Server_Maintenance')->processUnexpectedMaintenance();
            }

            if ($response->getMessages()->hasSystemErrorEntity()) {
                $exception = new \Ess\M2ePro\Model\Exception(
                    $this->getHelper('Module\Translation')->__(
                        "Internal Server Error(s) [%error_message%]",
                        $response->getMessages()->getCombinedSystemErrorsString()
                    ),
                    [],
                    0,
                    !$response->isServerInMaintenanceMode()
                );

                $this->getHelper('Module\Exception')->process($exception);
            }
        }
    }

    //########################################

    private function createFailedResponse($errorMessage)
    {
        $messages = [[
            Message::CODE_KEY   => 0,
            Message::TEXT_KEY   => $errorMessage,
            Message::TYPE_KEY   => Message::TYPE_ERROR,
            Message::SENDER_KEY => Message::SENDER_SYSTEM
        ]];

        $failedResponse = $this->modelFactory->getObject('Connector_Connection_Response');
        $failedResponse->initFromPreparedResponse([], $messages);
        $failedResponse->setRequestTime($this->requestTime);

        return $failedResponse;
    }

    //########################################

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
        return isset($this->requestsContainers[$key]) ? $this->requestsContainers[$key]->getRequest() : null;
    }

    // ----------------------------------------

    /**
     * @param $key
     * @return \Ess\M2ePro\Model\Connector\Connection\Response
     */
    public function getResponse($key)
    {
        return isset($this->responses[$key]) ? $this->responses[$key] : null;
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

    //########################################

    public function getHeaders(\Ess\M2ePro\Model\Connector\Connection\Request $request)
    {
        $command = $request->getCommand();

        return [
            'M2EPRO-API-VERSION: '.self::API_VERSION,
            'M2EPRO-API-COMPONENT: '.$request->getComponent(),
            'M2EPRO-API-COMPONENT-VERSION: '.$request->getComponentVersion(),
            'M2EPRO-API-COMMAND: /'.$command[0] .'/'.$command[1].'/'.$command[2].'/'
        ];
    }

    public function getBody(\Ess\M2ePro\Model\Connector\Connection\Request $request)
    {
        return [
            'api_version' => self::API_VERSION,
            'request'     => $this->getHelper('Data')->jsonEncode($request->getInfo()),
            'data'        => $this->getHelper('Data')->jsonEncode($request->getData()),
            'raw_data'    => $request->getRawData()
        ];
    }

    //########################################
}
