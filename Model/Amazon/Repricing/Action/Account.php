<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Repricing\Action;

class Account extends \Ess\M2ePro\Model\Amazon\Repricing\AbstractModel
{
    //########################################

    public function sendLinkActionData($backUrl)
    {
        $accountData = array(
            'merchant_id'      => $this->getAmazonAccount()->getMerchantId(),
            'marketplace_code' => $this->getAmazonAccount()->getMarketplace()->getCode(),
            'additional_data'  => $this->getHelper('Magento\Admin')->getCurrentInfo(),
        );

        return $this->sendData(
            \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_ACCOUNT_LINK,
            array('account' => $accountData),
            $backUrl
        );
    }

    public function sendUnlinkActionData($backUrl)
    {
        $skus = $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing')->getResource()->getSkus(
            $this->getAccount()
        );

        $offers  = array();
        foreach ($skus as $sku) {
            $offers[] = array('sku' => $sku);
        }

        return $this->sendData(
            \Ess\M2ePro\Helper\Component\Amazon\Repricing::COMMAND_ACCOUNT_UNLINK,
            array('offers' => $offers),
            $backUrl
        );
    }

    //########################################

    private function sendData($command, array $data, $backUrl)
    {
        $requestData = array(
            'request' => array(
                'back_url' => array(
                    'url'    => $backUrl,
                    'params' => array()
                )
            ),
            'data' => $this->getHelper('Data')->jsonEncode($data),
        );

        if ($this->getAmazonAccount()->isRepricing()) {
            $requestData['request']['auth'] = array(
                'account_token' => $this->getAmazonAccountRepricing()->getToken()
            );
        }

        try {
            $result = $this->getHelper('Component\Amazon\Repricing')->sendRequest($command, $requestData);
        } catch (\Exception $exception) {

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );

            $this->getHelper('Module\Exception')->process($exception, false);
            return false;
        }

        $response = $result['response'];
        $this->processErrorMessages($response);

        return !empty($response['request_token']) ? $response['request_token'] : false;
    }

    //########################################
}