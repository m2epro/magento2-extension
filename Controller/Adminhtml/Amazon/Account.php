<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account
 */
abstract class Account extends Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_configuration_accounts');
    }

    //########################################

    protected function addAccount($data)
    {
        if ($this->isAccountExists($data['merchant_id'], $data['marketplace_id'])) {
            throw new \Ess\M2ePro\Model\Exception(
                'An account with the same Amazon Merchant ID and Marketplace already exists.'
            );
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObject('Account');

        $this->modelFactory->getObject('Amazon_Account_Builder')->build($account, $data);

        try {
            $params = $this->getDataForServer($account);

            /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');

            /** @var \Ess\M2ePro\Model\Amazon\Connector\Account\Add\EntityRequester $connectorObj */
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
            $account->delete();

            throw $exception;
        }

        return $account;
    }

    protected function updateAccount($id, $data)
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $id);

        $oldData = array_merge($account->getOrigData(), $account->getChildObject()->getOrigData());

        $this->modelFactory->getObject('Amazon_Account_Builder')->build($account, $data);

        try {
            $params = $this->getDataForServer($account);

            if (!$this->isNeedSendDataToServer($params, $oldData)) {
                return $account;
            }

            /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');

            /** @var \Ess\M2ePro\Model\Amazon\Connector\Account\Update\EntityRequester $connectorObj */
            $connectorObj = $dispatcherObject->getConnector(
                'account',
                'update',
                'entityRequester',
                $params,
                $account
            );
            $dispatcherObject->process($connectorObj);
            $responseData = $connectorObj->getResponseData();

            $account->getChildObject()->addData(
                [
                    'info' => $this->getHelper('Data')->jsonEncode($responseData['info'])
                ]
            );
            $account->getChildObject()->save();
        } catch (\Exception $exception) {
            $this->modelFactory->getObject('Amazon_Account_Builder')->build($account, $oldData);

            throw $exception;
        }

        return $account;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Account $account
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @return array
     */
    protected function getDataForServer(\Ess\M2ePro\Model\Account $account)
    {
        return [
            'marketplace_id' => $account->getChildObject()->getMarketplaceId(),
            'merchant_id'    => $account->getChildObject()->getMerchantId(),
            'token'          => $account->getChildObject()->getToken(),
        ];

    }

    protected function isNeedSendDataToServer($newData, $oldData)
    {
        return !empty(array_diff_assoc($newData, $oldData));
    }

    protected function isAccountExists($merchantId, $marketplaceId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $collection */
        $collection = $this->amazonFactory->getObject('Account')->getCollection()
            ->addFieldToFilter('merchant_id', $merchantId)
            ->addFieldToFilter('marketplace_id', $marketplaceId);

        return $collection->getSize();
    }

    //########################################
}
