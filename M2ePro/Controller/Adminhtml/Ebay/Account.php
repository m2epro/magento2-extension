<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
 */
abstract class Account extends Main
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_configuration_accounts');
    }

    protected function sendDataToServer($id, $data)
    {
        // Add or update server
        // ---------------------------------------
        $requestData = [
            'mode' => $data['mode'] == \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION ? 'production' : 'sandbox',
            'token_session' => $data['token_session']
        ];

        if (isset($data['sell_api_token_session'])) {
            $requestData['sell_api_token_session'] = $data['sell_api_token_session'];
        }

        $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');

        if ((bool)$id) {
            /** @var \Ess\M2ePro\Model\Account $model */
            $model = $this->ebayFactory->getObjectLoaded('Account', $id);
            $requestData['title'] = $model->getTitle();

            $connectorObj = $dispatcherObject->getVirtualConnector(
                'account',
                'update',
                'entity',
                $requestData,
                null,
                null,
                $id
            );
        } else {
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'account',
                'add',
                'entity',
                $requestData,
                null,
                null,
                null
            );
        }

        try {
            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();
        } catch (\Exception $e) {
            $response = [];
        }

        // ---------------------------------------
        if (!isset($response['token_expired_date'])) {
            throw new \Ess\M2ePro\Model\Exception('Account is not added or updated. Try again later.');
        }

        isset($response['hash']) && $data['server_hash'] = $response['hash'];
        isset($response['info']['UserID']) && $data['user_id'] = $response['info']['UserID'];

        $data['info'] = $this->getHelper('Data')->jsonEncode($response['info']);
        $data['token_expired_date'] = $response['token_expired_date'];
        // ---------------------------------------

        // ---------------------------------------
        if (isset($response['sell_api_token_expired_date'])) {
            $data['sell_api_token_expired_date'] = $response['sell_api_token_expired_date'];
        }

        // ---------------------------------------

        return $data;
    }

    protected function updateAccount($id, $data)
    {
        // Change token
        // ---------------------------------------
        $isChangeTokenSession = false;
        if ((bool)$id) {
            $oldTokenSession = $this->ebayFactory->getCachedObjectLoaded('Account', $id)
                ->getChildObject()
                ->getTokenSession();
            $newTokenSession = $data['token_session'];
            if ($newTokenSession != $oldTokenSession) {
                $isChangeTokenSession = true;
            }
        } else {
            $isChangeTokenSession = true;
        }
        // ---------------------------------------

        // Add or update model
        // ---------------------------------------
        $model = $this->ebayFactory->getObject('Account');
        if ($id === null) {
            $model->setData($data);
        } else {
            $model->load($id);
            $model->addData($data);
            $model->getChildObject()->addData($data);
        }
        // ---------------------------------------

        $id = $model->save()->getId();

        // Update eBay store
        // ---------------------------------------
        if ($isChangeTokenSession || (int)$this->getRequest()->getParam('update_ebay_store')) {
            $ebayAccount = $model->getChildObject();
            $ebayAccount->updateEbayStoreInfo();

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
        // ---------------------------------------

        return $id;
    }
}
