<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

use Ess\M2ePro\Model\Account;
use Ess\M2ePro\Model\Amazon\Account as AmazonAccount;
use Ess\M2ePro\Model\Exception\Connection;

class Repricing
{
    public const COMMAND_ACCOUNT_GET = 'account/get';
    public const COMMAND_ACCOUNT_LINK = 'account/link';
    public const COMMAND_ACCOUNT_UNLINK = 'account/unlink';
    public const COMMAND_SYNCHRONIZE = 'synchronize';
    public const COMMAND_SYNCHRONIZE_USER_CHANGES = 'synchronize/userChanges';
    public const COMMAND_GOTO_SERVICE = 'goto_service';

    private const HTTP_STATUS_CODE_503 = 503;

    private const REQUEST_TIMEOUT = 300;

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Ess\M2ePro\Model\Config\Manager $config
     */
    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $config
    ) {
        $this->config = $config;
    }

    /**
     * @param $command
     * @param array $postData
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Connection
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function sendRequest($command, array $postData): array
    {
        $curlObject = curl_init();

        $url = $this->getBaseUrl() . $command;

        //set the url
        curl_setopt($curlObject, CURLOPT_URL, $url);

        // stop CURL from verifying the peer's certificate
        curl_setopt($curlObject, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlObject, CURLOPT_SSL_VERIFYHOST, false);

        // set the data body of the request
        curl_setopt($curlObject, CURLOPT_POST, true);
        curl_setopt($curlObject, CURLOPT_POSTFIELDS, http_build_query($postData, '', '&'));

        // set it to return the transfer as a string from curl_exec
        curl_setopt($curlObject, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlObject, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curlObject, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);

        curl_setopt($curlObject, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlObject, CURLOPT_POSTREDIR, 1);

        $response = curl_exec($curlObject);

        $curlInfo = curl_getinfo($curlObject);
        $errorNumber = curl_errno($curlObject);

        curl_close($curlObject);

        if ($this->isMaintenance($curlInfo)) {
            throw new \Ess\M2ePro\Model\Exception\Connection(
                'Scheduled maintenance is in progress. The action will be completed after the system update.'
            );
        }

        if ($response === false) {
            throw new Connection(
                (string) __(
                    'M2E Pro Server connection failed. Find the solution <a target="_blank" href="%1">here</a>',
                    'https://help.m2epro.com/support/solutions/articles/9000200887'
                ),
                [
                    'curl_error_number' => $errorNumber,
                    'curl_info' => $curlInfo,
                ]
            );
        }

        $responseDecoded = \Ess\M2ePro\Helper\Json::decode($response);
        if (!$responseDecoded || !is_array($responseDecoded)) {
            throw new \Ess\M2ePro\Model\Exception\Connection(
                'The Action was not completed because server responded with an incorrect response.',
                [
                    'raw_response' => $response,
                    'curl_info' => $curlInfo,
                ]
            );
        }

        return [
            'curl_error_number' => $errorNumber,
            'curl_info' => $curlInfo,
            'response' => $responseDecoded,
        ];
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        $baseUrl = $this->config->getGroupValue('/amazon/repricing/', 'base_url');

        return rtrim($baseUrl, '/') . '/';
    }

    /**
     * @param $command
     * @param $serverRequestToken
     *
     * @return string
     */
    public function prepareActionUrl($command, $serverRequestToken): string
    {
        return $this->getBaseUrl() . $command . '?' . http_build_query(['request_token' => $serverRequestToken]);
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return false|string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getManagementUrl(Account $account)
    {
        /** @var AmazonAccount $amazonAccount */
        $amazonAccount = $account->getChildObject();
        if (!$amazonAccount->isRepricing()) {
            return false;
        }

        return $this->getBaseUrl() . self::COMMAND_GOTO_SERVICE . '?' . http_build_query([
                'account_token' => $amazonAccount->getRepricing()->getToken(),
            ]);
    }

    /**
     * @param $curlInfo
     *
     * @return bool
     */
    private function isMaintenance($curlInfo): bool
    {
        return $curlInfo['http_code'] === self::HTTP_STATUS_CODE_503;
    }
}
