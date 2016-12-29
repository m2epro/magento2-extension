<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Amazon;

use Ess\M2ePro\Model\Account;
use Ess\M2ePro\Model\Amazon\Account as AmazonAccount;
use Ess\M2ePro\Model\Exception\Connection;

class Repricing extends \Ess\M2ePro\Helper\AbstractHelper
{
    const COMMAND_ACCOUNT_LINK             = 'account/link';
    const COMMAND_ACCOUNT_UNLINK           = 'account/unlink';
    const COMMAND_SYNCHRONIZE              = 'synchronize';
    const COMMAND_SYNCHRONIZE_USER_CHANGES = 'synchronize/userChanges';
    const COMMAND_GOTO_SERVICE             = 'goto_service';
    const COMMAND_OFFERS_ADD               = 'offers/add';
    const COMMAND_OFFERS_DETAILS           = 'offers/details';
    const COMMAND_OFFERS_EDIT              = 'offers/edit';
    const COMMAND_OFFERS_REMOVE            = 'offers/remove';
    const COMMAND_DATA_SET_REQUEST         = 'data/setRequest';
    const COMMAND_DATA_GET_RESPONSE        = 'data/getResponse';

    const REQUEST_TIMEOUT = 300;

    protected $moduleConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context,
        \Ess\M2ePro\Model\Config\Manager\Module $moduleConfig
    )
    {
        parent::__construct($helperFactory, $context);
        $this->moduleConfig = $moduleConfig;
    }

    //########################################

    public function isEnabled()
    {
        return (bool)$this->moduleConfig->getGroupValue('/amazon/repricing/', 'mode');
    }

    //########################################

    public function sendRequest($command, array $postData)
    {
        $curlObject = curl_init();

        $url = $this->getBaseUrl().$command;

        //set the url
        curl_setopt($curlObject, CURLOPT_URL, $url);

        // stop CURL from verifying the peer's certificate
        curl_setopt($curlObject, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlObject, CURLOPT_SSL_VERIFYHOST, false);

        // set the data body of the request
        curl_setopt($curlObject, CURLOPT_POST, true);
        curl_setopt($curlObject, CURLOPT_POSTFIELDS, http_build_query($postData,'','&'));

        // set it to return the transfer as a string from curl_exec
        curl_setopt($curlObject, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlObject, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curlObject, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);

        $response = curl_exec($curlObject);

        $curlInfo    = curl_getinfo($curlObject);
        $errorNumber = curl_errno($curlObject);

        curl_close($curlObject);

        if ($response === false) {

            throw new Connection(
                'The Action was not completed because connection with M2E Pro Repricing Service was not set.
                 There are several possible reasons: temporary connection problem – please wait and try again later;
                 block of outgoing connection by firewall',
                array(
                    'curl_error_number' => $errorNumber,
                    'curl_info'         => $curlInfo
                )
            );
        }

        return array(
            'curl_error_number' => $errorNumber,
            'curl_info'         => $curlInfo,
            'response'          => $response
        );
    }

    public function getBaseUrl()
    {
        $baseUrl = $this->moduleConfig->getGroupValue('/amazon/repricing/', 'base_url');
        return rtrim($baseUrl, '/') . '/';
    }

    //########################################

    public function prepareActionUrl($command, $serverRequestToken)
    {
        return $this->getBaseUrl().$command.'?'.http_build_query(array('request_token' => $serverRequestToken));
    }

    public function getManagementUrl(Account $account)
    {
        /** @var AmazonAccount $amazonAccount */
        $amazonAccount = $account->getChildObject();
        if (!$amazonAccount->isRepricing()) {
            return false;
        }

        return $this->getBaseUrl().self::COMMAND_GOTO_SERVICE.'?'.http_build_query(array(
            'account_token' => $amazonAccount->getRepricing()->getToken()
        ));
    }

    //########################################
}