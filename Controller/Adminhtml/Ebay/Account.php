<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay;

use Ess\M2ePro\Model\Ebay\Account as EbayAccount;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
 */
abstract class Account extends Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_configuration_accounts');
    }

    //########################################

    protected function addAccount($data)
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->ebayFactory->getObject('Account');

        $this->modelFactory->getObject('Ebay_Account_Builder')->build($account, $data);

        try {
            $params = $this->getDataForServer($data);

            /** @var $dispatcherObject \Ess\M2ePro\Model\Ebay\Connector\Dispatcher */
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
            $responseData = $connectorObj->getResponseData();

            if (!isset($responseData['token_expired_date'])) {
                throw new \Ess\M2ePro\Model\Exception('Account is not added or updated. Try again later.');
            }

            if (isset($responseData['info']['UserID'])) {
                $existsAccount = $this->isAccountExists($responseData['info']['UserID'], $account->getId());
                if (!empty($existsAccount)) {
                    throw new \Ess\M2ePro\Model\Exception('An account with the same eBay User ID already exists.');
                }
            }

            $dataForUpdate = [
                'info' => $this->getHelper('Data')->jsonEncode($responseData['info']),
                'token_expired_date' => $responseData['token_expired_date']
            ];

            isset($responseData['hash']) && $dataForUpdate['server_hash'] = $responseData['hash'];
            isset($responseData['info']['UserID']) && $dataForUpdate['user_id'] = $responseData['info']['UserID'];

            if (isset($responseData['sell_api_token_expired_date'])) {
                $dataForUpdate['sell_api_token_expired_date'] = $responseData['sell_api_token_expired_date'];
            }

            $account->getChildObject()->addData($dataForUpdate);
            $account->getChildObject()->save();
        } catch (\Exception $exception) {
            $account->delete();

            throw $exception;
        }

        // Update eBay store
        // ---------------------------------------
        $account->getChildObject()->updateEbayStoreInfo();

        if ($this->getHelper('Component_Ebay_Category_Store')->isExistDeletedCategories()) {
            $url = $this->getUrl('*/ebay_category/index', ['filter' => base64_encode('state=0')]);

            $this->messageManager->addWarning(
                $this->__(
                    'Some eBay Store Categories were deleted from eBay. Click '.
                    '<a target="_blank" href="%url%" class="external-link">here</a> to check.',
                    $url
                )
            );
        }

        // Update User Preferences
        // ---------------------------------------
        $account->getChildObject()->updateUserPreferences();

        return $account;
    }

    protected function updateAccount($id, $data)
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->ebayFactory->getObjectLoaded('Account', $id);

        $isChangeTokenSession = $data['token_session'] != $account->getChildObject()->getTokenSession();

        $oldData = array_merge($account->getOrigData(), $account->getChildObject()->getOrigData());

        $this->modelFactory->getObject('Ebay_Account_Builder')->build($account, $data);

        try {
            $params = $this->getDataForServer($data);
            $paramsOld = $this->getDataForServer($oldData);

            if (!$this->isNeedSendDataToServer($params, $paramsOld)) {
                return $account;
            }

            /** @var $dispatcherObject \Ess\M2ePro\Model\Ebay\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');

            $connectorObj = $dispatcherObject->getVirtualConnector(
                'account',
                'update',
                'entity',
                $params,
                null,
                null,
                $id
            );

            $dispatcherObject->process($connectorObj);
            $responseData = $connectorObj->getResponseData();

            if (!isset($responseData['token_expired_date'])) {
                throw new \Ess\M2ePro\Model\Exception('Account is not added or updated. Try again later.');
            }

            $dataForUpdate = [
                'info' => $this->getHelper('Data')->jsonEncode($responseData['info']),
                'token_expired_date' => $responseData['token_expired_date']
            ];

            isset($responseData['info']['UserID']) && $dataForUpdate['user_id'] = $responseData['info']['UserID'];

            if (isset($responseData['sell_api_token_expired_date'])) {
                $dataForUpdate['sell_api_token_expired_date'] = $responseData['sell_api_token_expired_date'];
            }

            $account->getChildObject()->addData($dataForUpdate);
            $account->getChildObject()->save();
        } catch (\Exception $exception) {
            $this->modelFactory->getObject('Ebay_Account_Builder')->build($account, $oldData);

            throw $exception;
        }

        // Update eBay store
        // ---------------------------------------
        if ($isChangeTokenSession || (int)$this->getRequest()->getParam('update_ebay_store')) {
            $account->getChildObject()->updateEbayStoreInfo();

            if ($this->getHelper('Component_Ebay_Category_Store')->isExistDeletedCategories()) {
                $url = $this->getUrl('*/ebay_category/index', ['filter' => base64_encode('state=0')]);

                $this->messageManager->addWarning(
                    $this->__(
                        'Some eBay Store Categories were deleted from eBay. Click '.
                        '<a target="_blank" href="%url%" class="external-link">here</a> to check.',
                        $url
                    )
                );
            }
        }

        // Update User Preferences
        // ---------------------------------------
        $account->getChildObject()->updateUserPreferences();

        return $account;
    }

    //########################################

    protected function getDataForServer($data)
    {
        $params = [
            'mode' => $data['mode'] == EbayAccount::MODE_PRODUCTION ? 'production' : 'sandbox',
            'token_session' => $data['token_session']
        ];

        if (isset($data['sell_api_token_session'])) {
            $params['sell_api_token_session'] = $data['sell_api_token_session'];
        }

        return $params;
    }

    protected function isNeedSendDataToServer($newData, $oldData)
    {
        return !empty(array_diff_assoc($newData, $oldData));
    }

    protected function isAccountExists($userId, $newAccountId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $collection */
        $collection = $this->ebayFactory->getObject('Account')->getCollection()
            ->addFieldToSelect('title')
            ->addFieldToFilter('user_id', $userId)
            ->addFieldToFilter('id', ['neq' => $newAccountId]);

        return $collection->getSize();
    }

    //########################################
}
