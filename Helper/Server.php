<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper;

class Server extends \Ess\M2ePro\Helper\AbstractHelper
{
    const MAX_INTERVAL_OF_RETURNING_TO_DEFAULT_BASEURL = 86400;

    protected $primary;
    protected $cacheConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Primary $primary,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->primary = $primary;
        $this->cacheConfig = $cacheConfig;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getEndpoint()
    {
        if ($this->getCurrentIndex() != $this->getDefaultIndex()) {

            $currentTimeStamp = $this->getHelper('Data')->getCurrentGmtDate(true);

            $interval = self::MAX_INTERVAL_OF_RETURNING_TO_DEFAULT_BASEURL;
            $switchingDateTime = $this->cacheConfig->getGroupValue('/server/location/','datetime_of_last_switching');

            if (is_null($switchingDateTime) || strtotime($switchingDateTime) + $interval <= $currentTimeStamp) {
                $this->setCurrentIndex($this->getDefaultIndex());
            }
        }

        return $this->getCurrentBaseUrl().'index.php';
    }

    public function switchEndpoint()
    {
        $previousIndex = $this->getCurrentIndex();
        $nextIndex = $previousIndex + 1;

        if (is_null($this->getBaseUrlByIndex($nextIndex))) {
            $nextIndex = 1;
        }

        if ($nextIndex == $previousIndex) {
            return false;
        }

        $this->setCurrentIndex($nextIndex);

        $this->cacheConfig->setGroupValue('/server/location/','datetime_of_last_switching',
                                        $this->getHelper('Data')->getCurrentGmtDate());

        return true;
    }

    //########################################

    public function getAdminKey()
    {
        return (string)$this->primary->getGroupValue('/server/', 'admin_key');
    }

    public function getApplicationKey()
    {
        return (string)$this->primary->getGroupValue('/server/', 'application_key');
    }

    //########################################

    public function sendRequest(array $postData,
                                array $headers = array(),
                                $serverBaseUrl = null,
                                $serverHostName = null,
                                $timeout = 300,
                                $tryToResendOnError = true,
                                $tryToSwitchEndpointOnError = true)
    {
        $curlObject = curl_init();

        // set the server we are using
        !$serverBaseUrl && $serverBaseUrl = $this->getEndpoint();
        curl_setopt($curlObject, CURLOPT_URL, $serverBaseUrl);

        // stop CURL from verifying the peer's certificate
        curl_setopt($curlObject, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlObject, CURLOPT_SSL_VERIFYHOST, false);

        // disable http headers
        curl_setopt($curlObject, CURLOPT_HEADER, false);

        // set the headers using the array of headers
        !$serverHostName && $serverHostName = $this->getCurrentHostName();
        $serverHostName && $headers['Host'] = $serverHostName;

        $preparedHeaders = array();
        foreach ($headers as $headerName => $headerValue) {
            $preparedHeaders[] = $headerName.':'.$headerValue;
        }

        curl_setopt($curlObject, CURLOPT_HTTPHEADER, $preparedHeaders);

        // set the data body of the request
        curl_setopt($curlObject, CURLOPT_POST, true);
        curl_setopt($curlObject, CURLOPT_POSTFIELDS, http_build_query($postData,'','&'));

        // set it to return the transfer as a string from curl_exec
        curl_setopt($curlObject, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlObject, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curlObject, CURLOPT_TIMEOUT, $timeout);

        $response = curl_exec($curlObject);

        $curlInfo     = curl_getinfo($curlObject);
        $errorNumber  = curl_errno($curlObject);
        $errorMessage = curl_error($curlObject);

        curl_close($curlObject);

        if ($response === false) {

            $switchingResult = false;
            $tryToSwitchEndpointOnError && $switchingResult = $this->switchEndpoint();

            if ($errorNumber !== CURLE_OPERATION_TIMEOUTED && $tryToResendOnError &&
                (!$tryToSwitchEndpointOnError || ($tryToSwitchEndpointOnError && $switchingResult))) {

                return $this->sendRequest(
                    $postData,
                    $headers,
                    $tryToSwitchEndpointOnError ? $this->getEndpoint() : $serverBaseUrl,
                    $tryToSwitchEndpointOnError ? $this->getCurrentHostName() : $serverHostName,
                    $timeout,
                    false,
                    $tryToSwitchEndpointOnError
                );
            }

            $errorMsg = 'The Action was not completed because connection with M2E Pro Server was not set.
            There are several possible reasons:  temporary connection problem – please wait and try again later;
            block of outgoing connection by firewall – please, ensure that connection to s1.m2epro.com and
            s2.m2epro.com, port 443 is allowed; CURL library is not installed or it does not support HTTPS Protocol –
            please, install/update CURL library on your server and ensure it supports HTTPS Protocol.
            More information you can find <a target="_blank" href="'.
            $this->getHelper('Module\Support')
                ->getKnowledgebaseUrl('664870-issues-with-m2e-pro-server-connection')
                .'">here</a>';

            throw new \Ess\M2ePro\Model\Exception\Connection($errorMsg,
                                                             array('curl_error_number'  => $errorNumber,
                                                                   'curl_error_message' => $errorMessage,
                                                                   'curl_info'          => $curlInfo));
        }

        return array(
            'curl_error_number' => $errorNumber,
            'curl_info'         => $curlInfo,
            'response'          => $response
        );
    }

    //########################################

    private function getCurrentBaseUrl()
    {
        return $this->getBaseUrlByIndex($this->getCurrentIndex());
    }

    private function getCurrentHostName()
    {
        return $this->getHostNameByIndex($this->getCurrentIndex());
    }

    // ---------------------------------------

    private function getDefaultIndex()
    {
        $index = (int)$this->primary->getGroupValue('/server/location/','default_index');

        if ($index <= 0 || $index > $this->getMaxBaseUrlIndex()) {
            $this->setDefaultIndex($index = 1);
        }

        return $index;
    }

    private function getCurrentIndex()
    {
        $index = (int)$this->cacheConfig->getGroupValue('/server/location/','current_index');

        if ($index <= 0 || $index > $this->getMaxBaseUrlIndex()) {
            $this->setCurrentIndex($index = $this->getDefaultIndex());
        }

        return $index;
    }

    // ---------------------------------------

    private function setDefaultIndex($index)
    {
        $this->primary->setGroupValue('/server/location/','default_index',$index);
    }

    private function setCurrentIndex($index)
    {
        $this->cacheConfig->setGroupValue('/server/location/','current_index',$index);
    }

    //########################################

    private function getMaxBaseUrlIndex()
    {
        $index = 1;

        for ($tempIndex=2; $tempIndex<100; $tempIndex++) {

            $tempBaseUrl = $this->getBaseUrlByIndex($tempIndex);

            if (!is_null($tempBaseUrl)) {
                $index = $tempIndex;
            } else {
                break;
            }
        }

        return $index;
    }

    private function getBaseUrlByIndex($index)
    {
        return $this->primary->getGroupValue('/server/location/'.$index.'/','baseurl');
    }

    private function getHostNameByIndex($index)
    {
        return $this->primary->getGroupValue('/server/location/'.$index.'/','hostname');
    }

    //########################################
}