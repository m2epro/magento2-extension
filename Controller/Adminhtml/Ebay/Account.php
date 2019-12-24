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
        $requestMode = $data['mode'] == \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION ? 'production' : 'sandbox';

        $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');

        if ((bool)$id) {
            $model = $this->ebayFactory->getObjectLoaded('Account', $id);

            $connectorObj = $dispatcherObject->getVirtualConnector(
                'account',
                'update',
                'entity',
                ['title'         => $model->getTitle(),
                    'mode'          => $requestMode,
                    'token_session' => $data['token_session']],
                null,
                null,
                $id
            );
        } else {
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'account',
                'add',
                'entity',
                ['mode' => $requestMode,
                    'token_session' => $data['token_session']],
                null,
                null,
                null
            );
        }

        $dispatcherObject->process($connectorObj);
        $response = $connectorObj->getResponseData();

        if (!isset($response['token_expired_date'])) {
            throw new \Ess\M2ePro\Model\Exception('Account is not added or updated. Try again later.');
        }

        isset($response['hash']) && $data['server_hash'] = $response['hash'];
        isset($response['info']['UserID']) && $data['user_id'] = $response['info']['UserID'];

        $data['info'] = $this->getHelper('Data')->jsonEncode($response['info']);
        $data['token_expired_date'] = $response['token_expired_date'];
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
