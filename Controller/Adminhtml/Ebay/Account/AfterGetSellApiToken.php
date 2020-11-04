<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\AfterGetToken
 */
class AfterGetSellApiToken extends Account
{
    public function execute()
    {
        // Get eBay session id
        // ---------------------------------------
        $sessionId = base64_decode($this->getRequest()->getParam('code'));
        $sessionId === null && $this->_redirect('*/*/index');
        // ---------------------------------------

        // Get account form data
        // ---------------------------------------
        $this->getHelper('Data\Session')->setValue('get_sell_api_token_account_token_session', $sessionId);
        // ---------------------------------------

        // Goto account add or edit page
        // ---------------------------------------
        $accountId = (int)$this->getHelper('Data\Session')->getValue('get_sell_api_token_account_id', true);

        if ($accountId == 0) {
            $this->_redirect('*/*/index');
        }

        $this->getMessageManager()->addSuccess($this->__('Sell API token was obtained'));
        $this->_redirect('*/*/edit', ['id' => $accountId, '_current' => true]);
        // ---------------------------------------
    }
}
