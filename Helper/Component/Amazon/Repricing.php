<?php

/*
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
    public const COMMAND_ACCOUNT_LINK = 'account/link';
    public const COMMAND_ACCOUNT_UNLINK = 'account/unlink';
    public const COMMAND_SYNCHRONIZE = 'synchronize';
    public const COMMAND_SYNCHRONIZE_USER_CHANGES = 'synchronize/userChanges';
    public const COMMAND_GOTO_SERVICE = 'goto_service';
    public const COMMAND_OFFERS_ADD = 'offers/add';
    public const COMMAND_OFFERS_DETAILS = 'offers/details';
    public const COMMAND_OFFERS_EDIT = 'offers/edit';
    public const COMMAND_OFFERS_REMOVE = 'offers/remove';
    public const COMMAND_DATA_SET_REQUEST = 'data/setRequest';
    public const COMMAND_DATA_GET_RESPONSE = 'data/getResponse';

    private const REQUEST_TIMEOUT = 300;

    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $moduleTranslation;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $moduleSupport;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Module\Translation $moduleTranslation,
        \Ess\M2ePro\Helper\Module\Support $moduleSupport,
        \Ess\M2ePro\Model\Config\Manager $config
    ) {
        $this->helperData = $helperData;
        $this->moduleTranslation = $moduleTranslation;
        $this->moduleSupport = $moduleSupport;
        $this->config = $config;
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->config->getGroupValue('/amazon/repricing/', 'mode');
    }

    // ----------------------------------------

    public function sendRequest($command, array $postData)
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

        $response = curl_exec($curlObject);

        $curlInfo = curl_getinfo($curlObject);
        $errorNumber = curl_errno($curlObject);

        curl_close($curlObject);

        if ($response === false) {
            throw new Connection(
                $this->moduleTranslation->__(
                    'M2E Pro Server connection failed. Find the solution <a target="_blank" href="%url%">here</a>',
                    $this->moduleSupport->getKnowledgebaseArticleUrl('664870')
                ),
                [
                    'curl_error_number' => $errorNumber,
                    'curl_info'         => $curlInfo,
                ]
            );
        }

        $responseDecoded = $this->helperData->jsonDecode($response);
        if (!$responseDecoded || !is_array($responseDecoded)) {
            throw new \Ess\M2ePro\Model\Exception\Connection(
                'The Action was not completed because server responded with an incorrect response.',
                [
                    'raw_response' => $response,
                    'curl_info'    => $curlInfo,
                ]
            );
        }

        return [
            'curl_error_number' => $errorNumber,
            'curl_info'         => $curlInfo,
            'response'          => $responseDecoded,
        ];
    }

    public function getBaseUrl()
    {
        $baseUrl = $this->config->getGroupValue('/amazon/repricing/', 'base_url');

        return rtrim($baseUrl, '/') . '/';
    }

    // ----------------------------------------

    public function prepareActionUrl($command, $serverRequestToken)
    {
        return $this->getBaseUrl() . $command . '?' . http_build_query(['request_token' => $serverRequestToken]);
    }

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
}
