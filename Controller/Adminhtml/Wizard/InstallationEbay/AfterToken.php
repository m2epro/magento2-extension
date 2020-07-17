<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;
use Ess\M2ePro\Model\Ebay\Account as AccountModel;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay\AfterToken
 */
class AfterToken extends InstallationEbay
{
    public function execute()
    {
        $tokenSessionId = $this->getHelper('Data\Session')->getValue('token_session_id', true);

        if (!$tokenSessionId) {
            $this->messageManager->addError($this->__('Token is not defined'));
            return $this->_redirect('*/*/installation');
        }

        $accountMode = $this->getRequest()->getParam('mode');

        $requestParams = [
            'mode' => $accountMode,
            'token_session' => $tokenSessionId
        ];

        $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector(
            'account',
            'add',
            'entity',
            $requestParams,
            null,
            null,
            null
        );

        $dispatcherObject->process($connectorObj);
        $response = array_filter($connectorObj->getResponseData());

        if (empty($response)) {
            $this->messageManager->addError($this->__('Account Add Entity failed.'));
            return $this->_redirect('*/*/installation');
        }

        if ($accountMode == 'sandbox') {
            $accountMode = AccountModel::MODE_SANDBOX;
        } else {
            $accountMode = AccountModel::MODE_PRODUCTION;
        }

        $data = array_merge(
            $this->getEbayAccountDefaultSettings(),
            [
                'title' => $response['info']['UserID'],
                'user_id' => $response['info']['UserID'],
                'mode' => $accountMode,
                'info' => $this->getHelper('Data')->jsonEncode($response['info']),
                'server_hash' => $response['hash'],
                'token_session' => $tokenSessionId,
                'token_expired_date' => $response['token_expired_date']
            ]
        );

        $accountModel = $this->ebayFactory->getObject('Account');
        $this->modelFactory->getObject('Ebay_Account_Builder')->build($accountModel, $data);
        $accountModel->getChildObject()->updateEbayStoreInfo();

        $this->setStep($this->getNextStep());

        return $this->_redirect('*/*/installation');
    }

    /**
     * @return array
     */
    private function getEbayAccountDefaultSettings()
    {
        $data = $this->modelFactory->getObject('Ebay_Account_Builder')->getDefaultData();

        $data['marketplaces_data'] = [];

        $data['other_listings_synchronization'] = 0;

        $data['magento_orders_settings']['listing_other']['store_id'] = $this->getHelper('Magento\Store')
            ->getDefaultStoreId();
        $data['magento_orders_settings']['qty_reservation']['days'] = 0;

        return $data;
    }
}
