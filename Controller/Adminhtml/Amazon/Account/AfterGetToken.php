<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Model\Amazon\Account as AccountModel;

class AfterGetToken extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Account
{
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (empty($params)) {
            return $this->_redirect('*/*/new');
        }

        $requiredFields = array(
            'Merchant',
            'MWSAuthToken',
        );

        foreach ($requiredFields as $requiredField) {
            if (!isset($params[$requiredField])) {
                // M2ePro_TRANSLATIONS
                // The Amazon token obtaining is currently unavailable.
                $error = $this->__('The Amazon token obtaining is currently unavailable.');
                $this->messageManager->addError($error);

                return $this->_redirect('*/*/new');
            }
        }

        $this->getHelper('Data\Session')->setValue('merchant_id', $params['Merchant']);
        $this->getHelper('Data\Session')->setValue('mws_token', $params['MWSAuthToken']);

        $accountId = $this->getHelper('Data\Session')->getValue('account_id');

        if ((int)$accountId <= 0) {
            return $this->_redirect('*/*/new');
        } else {
            return $this->_redirect('*/*/edit', ['id' => $accountId]);
        }
    }
}