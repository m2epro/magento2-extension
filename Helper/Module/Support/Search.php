<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module\Support;

class Search
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper
    ) {
        $this->supportHelper = $supportHelper;
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param string $query
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function process(string $query): array
    {
        if (empty($query)) {
            return [];
        }

        $params = [
            'query' => strip_tags($query),
            'count' => 10,
        ];

        $results = [];
        $response = $this->sendRequestAsGet($params);

        if ($response !== false) {
            $results = (array)$this->dataHelper->jsonDecode($response);
        }

        return $results;
    }

    /**
     * @param array $params
     *
     * @return bool|string
     */
    private function sendRequestAsGet(array $params)
    {
        $curlObject = curl_init();

        $url = $this->supportHelper->getSupportUrl('extension/search');
        $url = $url . '?' . http_build_query($params, '', '&');
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
}
