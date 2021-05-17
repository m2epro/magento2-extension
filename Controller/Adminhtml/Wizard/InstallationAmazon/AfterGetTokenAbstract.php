<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon\AfterGetTokenAbstract
 */
abstract class AfterGetTokenAbstract extends InstallationAmazon
{
    //########################################

    public function execute()
    {
        try {
            $accountData = $this->getAccountData();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            $this->messageManager->addError($this->__($exception->getMessage()));

            return $this->indexAction();
        }

        $account = $this->amazonFactory->getObject('Account');
        $this->modelFactory->getObject('Amazon_Account_Builder')->build($account, $accountData);

        try {
            $params = [
                'marketplace_id' => $accountData['marketplace_id'],
                'merchant_id'    => $accountData['merchant_id'],
                'token'          => $accountData['token'],
            ];

            /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');

            $connectorObj = $dispatcherObject->getConnector(
                'account',
                'add',
                'entityRequester',
                $params,
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
            $this->messageManager->addError($this->__($exception->getMessage()));

            $account->delete();

            return $this->indexAction();
        }

        $this->activeRecordFactory->getObjectLoaded('Marketplace', $accountData['marketplace_id'])
            ->setData('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
            ->save();

        $this->setStep($this->getNextStep());

        return $this->_redirect('*/*/installation');
    }

    abstract protected function getAccountData();

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getAmazonAccountDefaultSettings()
    {
        $data = $this->modelFactory->getObject('Amazon_Account_Builder')->getDefaultData();

        $data['other_listings_synchronization'] = 0;
        $data['other_listings_mapping_mode'] = 0;

        $data['magento_orders_settings']['listing_other']['store_id'] = $this->getHelper('Magento\Store')
            ->getDefaultStoreId();

        $data['magento_orders_settings']['tax']['excluded_states'] = implode(
            ',',
            $data['magento_orders_settings']['tax']['excluded_states']
        );

        return $data;
    }

    //########################################
}
