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
    public function execute()
    {
        try {
            $accountData = $this->getAccountData();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            $this->messageManager->addError($this->__($exception->getMessage()));

            return $this->indexAction();
        }

        $accountModel = $this->amazonFactory->getObject('Account');
        $this->modelFactory->getObject('Amazon_Account_Builder')->build($accountModel, $accountData);

        try {
            /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');

            $params = [
                'title'            => $accountData['merchant_id'],
                'marketplace_id'   => $accountData['marketplace_id'],
                'merchant_id'      => $accountData['merchant_id'],
                'token'            => $accountData['token'],
            ];

            $connectorObj = $dispatcherObject->getConnector(
                'account',
                'add',
                'entityRequester',
                $params,
                $accountModel->getId()
            );
            $dispatcherObject->process($connectorObj);
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            $this->messageManager->addError($this->__($exception->getMessage()));
            $accountModel->delete();

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
}
