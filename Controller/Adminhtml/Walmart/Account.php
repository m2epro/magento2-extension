<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart;

use Ess\M2ePro\Model\Walmart\Account as WalmartAccount;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Account
 */
abstract class Account extends Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_configuration_accounts');
    }

    //########################################

    protected function addAccount($data)
    {
        $searchField = empty($data['client_id']) ? 'consumer_id' : 'client_id';
        $searchValue = empty($data['client_id']) ? $data['consumer_id'] : $data['client_id'];

        if ($this->isAccountExists($searchField, $searchValue)) {
            throw new \Ess\M2ePro\Model\Exception(
                'An account with the same Walmart Client ID already exists.'
            );
        }

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->walmartFactory->getObject('Account');

        $this->modelFactory->getObject('Walmart_Account_Builder')->build($account, $data);

        try {
            $params = $this->getDataForServer($data);

            /** @var $dispatcherObject \Ess\M2ePro\Model\Walmart\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

            /** @var \Ess\M2ePro\Model\Walmart\Connector\Account\Add\EntityRequester $connectorObj */
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
        $account = $this->walmartFactory->getObjectLoaded('Account', $id);

        $oldData = array_merge($account->getOrigData(), $account->getChildObject()->getOrigData());

        $this->modelFactory->getObject('Walmart_Account_Builder')->build($account, $data);

        try {
            $params = $this->getDataForServer($data);

            if (!$this->isNeedSendDataToServer($params, $oldData)) {
                return $account;
            }

            /** @var $dispatcherObject \Ess\M2ePro\Model\Walmart\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

            /** @var \Ess\M2ePro\Model\Walmart\Connector\Account\Update\EntityRequester $connectorObj */
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
            $this->modelFactory->getObject('Walmart_Account_Builder')->build($account, $oldData);

            throw $exception;
        }

        return $account;
    }

    //########################################

    protected function getDataForServer($data)
    {
        $params = [
            'marketplace_id' => (int)$data['marketplace_id']
        ];

        if ($data['marketplace_id'] == \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_US) {
            $params['client_id'] = $data['client_id'];
            $params['client_secret'] = $data['client_secret'];
        } else {
            $params['consumer_id'] = $data['consumer_id'];
            $params['private_key'] = $data['private_key'];
        }

        return $params;
    }

    protected function isNeedSendDataToServer($newData, $oldData)
    {
        return !empty(array_diff_assoc($newData, $oldData));
    }

    protected function isAccountExists($search, $value)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $collection */
        $collection = $this->walmartFactory->getObject('Account')->getCollection()
            ->addFieldToFilter($search, $value);

        return $collection->getSize();
    }

    //########################################
}
