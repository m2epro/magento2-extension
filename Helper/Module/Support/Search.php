<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Support;

class Search extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->moduleConfig = $moduleConfig;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function process($query)
    {
        if (empty($query)) {
            return array();
        }

        $params = array(
            'query' => strip_tags($query),
            'count' => 10
        );

        $results = array();
        $response = $this->sendRequestAsGet($params);

        if ($response !== false) {
            $results = (array)$this->getHelper('Data')->jsonDecode($response);
        }

        return $results;
    }

    //########################################

    private function sendRequestAsGet($params)
    {
        $curlObject = curl_init();

        $url = $this->getHelper('Module\Support')->getSupportUrl() . '/extension/search/';
        $url = $url . '?'.http_build_query($params,'','&');
        curl_setopt($curlObject, CURLOPT_URL, $url);

        curl_setopt($curlObject, CURLOPT_FOLLOWLOCATION, true);

        // stop CURL from verifying the peer's certificate
        curl_setopt($curlObject, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlObject, CURLOPT_SSL_VERIFYHOST, false);

        // disable http headers
        curl_setopt($curlObject, CURLOPT_HEADER, false);
        curl_setopt($curlObject, CURLOPT_POST, false);

        // set it to return the transfer as a string from curl_exec
        curl_setopt($curlObject, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlObject, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curlObject, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($curlObject);
        curl_close($curlObject);

        return $response;
    }

    //########################################
}