<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\BeforeGetToken
 */
class BeforeGetSellApiToken extends Account
{
    public function execute()
    {
        // Get and save form data
        // ---------------------------------------
        $accountId = $this->getRequest()->getParam('id', 0);
        $accountMode = (int)$this->getRequest()->getParam('mode', \Ess\M2ePro\Model\Ebay\Account::MODE_SANDBOX);
        // ---------------------------------------

        // Get and save session id
        // ---------------------------------------
        $mode = $accountMode == \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION ? 'production' : 'sandbox';

        try {
            $backUrl = $this->getUrl('*/*/afterGetSellApiToken', ['_current' => true]);

            $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'account',
                'get',
                'grandAccessUrl',
                ['back_url' => $backUrl, 'mode' => $mode, 'auth_type' => 'oauth'],
                null,
                null,
                null,
                $mode
            );

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            $error = 'The eBay Sell token obtaining is currently unavailable.<br/>Reason: %error_message%';
            $error = $this->__($error, $exception->getMessage());

            $this->getMessageManager()->addError($error);

            $this->_redirect($this->getUrl('*/*/index'));
            return;
        }

        $this->getHelper('Data\Session')->setValue('get_sell_api_token_account_id', $accountId);
        $this->getHelper('Data\Session')->setValue('get_sell_api_token_account_mode', $accountMode);

        $this->_redirect($response['url']);
        // ---------------------------------------
    }
}
