<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;
use Ess\M2ePro\Model\Ebay\Account as EbayAccount;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay\AfterToken
 */
class AfterToken extends InstallationEbay
{
    //########################################
    
    public function execute()
    {
        $tokenSessionId = $this->getHelper('Data\Session')->getValue('token_session_id', true);

        if (!$tokenSessionId) {
            $this->messageManager->addError($this->__('Token is not defined'));

            return $this->_redirect('*/*/installation');
        }

        $accountMode = $this->getRequest()->getParam('mode');

        $params = [
            'mode'          => $accountMode,
            'token_session' => $tokenSessionId
        ];

        $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector(
            'account',
            'add',
            'entity',
            $params,
            null,
            null,
            null
        );

        $dispatcherObject->process($connectorObj);
        $responseData = array_filter($connectorObj->getResponseData());

        if (empty($responseData)) {
            $this->messageManager->addError($this->__('Account Add Entity failed.'));

            return $this->_redirect('*/*/installation');
        }

        if ($accountMode == 'sandbox') {
            $accountMode = EbayAccount::MODE_SANDBOX;
        } else {
            $accountMode = EbayAccount::MODE_PRODUCTION;
        }

        $data = array_merge(
            $this->getEbayAccountDefaultSettings(),
            [
                'title'   => $responseData['info']['UserID'],
                'user_id' => $responseData['info']['UserID'],
                'mode'    => $accountMode,
                'info'    => $this->getHelper('Data')->jsonEncode($responseData['info']),
                'server_hash'   => $responseData['hash'],
                'token_session' => $tokenSessionId,
                'token_expired_date' => $responseData['token_expired_date']
            ]
        );

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->ebayFactory->getObject('Account');
        $this->modelFactory->getObject('Ebay_Account_Builder')->build($account, $data);
        $account->getChildObject()->updateEbayStoreInfo();

        $this->setStep($this->getNextStep());

        return $this->_redirect('*/*/installation');
    }

    /**
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
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

    //########################################
}
