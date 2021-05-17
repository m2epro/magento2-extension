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
    //########################################

    public function execute()
    {
        $sessionId = $this->getHelper('Data\Session')->getValue('get_token_session_id', true);
        $sessionId === null && $this->_redirect('*/*/index');

        $this->getHelper('Data\Session')->setValue('get_token_account_token_session', $sessionId);

        $id = (int)$this->getHelper('Data\Session')->getValue('get_token_account_id', true);

        if ((int)$id <= 0) {
            return $this->_redirect(
                '*/*/new',
                [
                    'is_show_tables' => true,
                    '_current'       => true
                ]
            );
        }

        $data = [
            'mode' => $this->getHelper('Data\Session')->getValue('get_token_account_mode'),
            'token_session' => $sessionId
        ];

        try {
            $this->updateAccount($id, $data);
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            $this->messageManager->addError($this->__(
                'The Ebay access obtaining is currently unavailable.<br/>Reason: %error_message%',
                $exception->getMessage()
            ));

            return $this->_redirect('*/ebay_account');
        }

        $this->messageManager->addSuccess($this->__('Token was saved'));

        return $this->_redirect('*/*/edit', ['id' => $id, '_current' => true]);
    }

    //########################################
}
