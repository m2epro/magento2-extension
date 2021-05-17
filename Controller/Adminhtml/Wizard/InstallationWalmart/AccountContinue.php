<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;
use Ess\M2ePro\Model\Walmart\Account as WalmartAccount;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart\AccountContinue
 */
class AccountContinue extends InstallationWalmart
{
    //########################################

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (empty($params)) {
            return $this->indexAction();
        }

        if (!$this->validateRequiredParams($params)) {
            $this->setJsonContent(['message' => $this->__('You should fill all required fields.')]);

            return $this->getResult();
        }

        $accountData = [];

        $requiredFields = [
            'marketplace_id',
            'consumer_id',
            'private_key',
            'client_id',
            'client_secret'
        ];

        foreach ($requiredFields as $requiredField) {
            if (!empty($params[$requiredField])) {
                $accountData[$requiredField] = $params[$requiredField];
            }
        }

        /** @var $marketplaceObject \Ess\M2ePro\Model\Marketplace */
        $marketplaceObject = $this->walmartFactory->getCachedObjectLoaded(
            'Marketplace',
            $params['marketplace_id']
        );
        $marketplaceObject->setData('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)->save();

        $accountData = array_merge(
            $this->getAccountDefaultSettings(),
            [
                'title' => "Default - {$marketplaceObject->getCode()}",
            ],
            $accountData
        );

        /** @var $account \Ess\M2ePro\Model\Account */
        $account = $this->walmartFactory->getObject('Account');
        $this->modelFactory->getObject('Walmart_Account_Builder')->build($account, $accountData);

        try {
            $requestData = [
                'marketplace_id' => $params['marketplace_id']
            ];

            if ($params['marketplace_id'] == \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US) {
                $requestData['client_id'] = $params['client_id'];
                $requestData['client_secret'] = $params['client_secret'];
            } else {
                $requestData['consumer_id'] = $params['consumer_id'];
                $requestData['private_key'] = $params['private_key'];
            }

            /** @var $dispatcherObject \Ess\M2ePro\Model\Walmart\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

            $connectorObj = $dispatcherObject->getConnector(
                'account',
                'add',
                'entityRequester',
                $requestData,
                $account
            );
            $dispatcherObject->process($connectorObj);
            $responseData = $connectorObj->getResponseData();

            $account->getChildObject()->addData(
                [
                    'server_hash' => $responseData['hash'],
                    'info'        => $this->getHelper('Data')->jsonEncode($responseData['info'])
                ]
            );
            $account->getChildObject()->save();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            $account->delete();

            $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
                $this->modelFactory->getObject('Servicing_Task_License')->getPublicNick()
            );

            $error = 'The Walmart access obtaining is currently unavailable.<br/>Reason: %error_message%';

            if (!$this->getHelper('Module\License')->isValidDomain() ||
                !$this->getHelper('Module\License')->isValidIp()) {
                $error .= '</br>Go to the <a href="%url%" target="_blank">License Page</a>.';
                $error = $this->__(
                    $error,
                    $exception->getMessage(),
                    $this->getHelper('View\Configuration')->getLicenseUrl(['wizard' => 1])
                );
            } else {
                $error = $this->__($error, $exception->getMessage());
            }

            $this->setJsonContent(['message' => $error]);

            return $this->getResult();
        }

        $this->setStep($this->getNextStep());

        $this->setJsonContent(['result' => true]);

        return $this->getResult();
    }

    private function validateRequiredParams($params)
    {
        if (empty($params['marketplace_id'])) {
            return false;
        }

        if ($params['marketplace_id'] == \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US) {
            if (empty($params['client_id']) || empty($params['client_secret'])) {
                return false;
            }
        } else {
            if (empty($params['consumer_id']) || empty($params['private_key'])) {
                return false;
            }
        }

        return true;
    }

    private function getAccountDefaultSettings()
    {
        $data = $this->modelFactory->getObject('Walmart_Account_Builder')->getDefaultData();

        $data['other_listings_synchronization'] = 0;
        $data['other_listings_mapping_mode'] = 0;

        $data['magento_orders_settings']['listing_other']['store_id'] = $this->getHelper('Magento\Store')
            ->getDefaultStoreId();

        return $data;
    }

    //########################################
}
