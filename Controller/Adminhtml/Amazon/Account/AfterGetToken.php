<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Model\Amazon\Account as AccountModel;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\AfterGetToken
 */
class AfterGetToken extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Account
{
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (empty($params)) {
            return $this->_redirect('*/*/new', [
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]);
        }

        $requiredFields = [
            'Merchant',
            'MWSAuthToken',
        ];

        foreach ($requiredFields as $requiredField) {
            if (!isset($params[$requiredField])) {
                // M2ePro_TRANSLATIONS
                // The Amazon token obtaining is currently unavailable.
                $error = $this->__('The Amazon token obtaining is currently unavailable.');
                $this->messageManager->addError($error);

                return $this->_redirect('*/*/new', [
                    'close_on_save' => $this->getRequest()->getParam('close_on_save')
                ]);
            }
        }

        $this->getHelper('Data\Session')->setValue('merchant_id', $params['Merchant']);
        $this->getHelper('Data\Session')->setValue('mws_token', $params['MWSAuthToken']);

        $accountId = $this->getHelper('Data\Session')->getValue('account_id');

        if ((int)$accountId <= 0) {
            return $this->_redirect('*/*/new', [
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]);
        } else {
            return $this->_redirect('*/*/edit', [
                'id' => $accountId, 'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]);
        }
    }
}
