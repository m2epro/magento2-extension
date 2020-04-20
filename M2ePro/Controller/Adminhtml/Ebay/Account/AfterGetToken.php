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
class AfterGetToken extends Account
{
    public function execute()
    {
        // Get eBay session id
        // ---------------------------------------
        $sessionId = $this->getHelper('Data\Session')->getValue('get_token_session_id', true);
        $sessionId === null && $this->_redirect('*/*/index');
        // ---------------------------------------

        // Get account form data
        // ---------------------------------------
        $this->getHelper('Data\Session')->setValue('get_token_account_token_session', $sessionId);
        // ---------------------------------------

        // Goto account add or edit page
        // ---------------------------------------
        $accountId = (int)$this->getHelper('Data\Session')->getValue('get_token_account_id', true);

        if ($accountId == 0) {
            $this->_redirect('*/*/new', ['_current' => true]);
        } else {
            $data = [];
            $data['mode'] = $this->getHelper('Data\Session')->getValue('get_token_account_mode');
            $data['token_session'] = $sessionId;

            $data = $this->sendDataToServer($accountId, $data);
            $id = $this->updateAccount($accountId, $data);

            $this->messageManager->addSuccess($this->__('Token was successfully saved'));
            $this->_redirect('*/*/edit', ['id' => $id, '_current' => true]);
        }
        // ---------------------------------------
    }
}
