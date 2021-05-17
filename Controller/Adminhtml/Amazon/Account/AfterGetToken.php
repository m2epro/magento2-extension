<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\AfterGetToken
 */
class AfterGetToken extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Account
{
    //########################################

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
                $error = $this->__('The Amazon token obtaining is currently unavailable.');
                $this->messageManager->addError($error);

                return $this->_redirect('*/*/new', [
                    'close_on_save' => $this->getRequest()->getParam('close_on_save')
                ]);
            }
        }

        $this->getHelper('Data\Session')->setValue('merchant_id', $params['Merchant']);
        $this->getHelper('Data\Session')->setValue('mws_token', $params['MWSAuthToken']);

        $id = $this->getHelper('Data\Session')->getValue('account_id');

        if ((int)$id <= 0) {
            return $this->_redirect('*/*/new', [
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ]);
        }

        try {
            $this->updateAccount(
                $id,
                [
                    'merchant_id' => $params['Merchant'],
                    'token'       => $params['MWSAuthToken']
                ]
            );
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            $this->messageManager->addError($this->__(
                'The Amazon access obtaining is currently unavailable.<br/>Reason: %error_message%',
                $exception->getMessage()
            ));

            return $this->_redirect('*/amazon_account');
        }

        $this->messageManager->addSuccess($this->__('Token was saved'));

        return $this->_redirect('*/*/edit', [
            'id' => $id, 'close_on_save' => $this->getRequest()->getParam('close_on_save')
        ]);
    }

    //########################################
}
