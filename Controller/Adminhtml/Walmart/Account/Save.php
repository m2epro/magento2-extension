<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Account\Save
 */
class Save extends Account
{
    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
        }

        $id = $this->getRequest()->getParam('id');
        $isEdit = $id !== null;

        $searchField = empty($post['client_id']) ? 'consumer_id' : 'client_id';
        $searchValue = empty($post['client_id']) ? $post['consumer_id'] : $post['client_id'];

        $accountExists = $this->getExistsAccount($searchField, $searchValue);
        if (empty($id) && !empty($accountExists)) {
            $this->getMessageManager()->addError(
                $this->__('An account with the same Walmart Client ID already exists.')
            );

            return $this->_redirect('*/*/new');
        }

        // Add or update model
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\Walmart\Account $model */
        if ($id === null) {
            $model = $this->walmartFactory->getObject('Account');
        } else {
            $model = $this->walmartFactory->getObjectLoaded('Account', $id);
        }

        $oldData = $model->getOrigData();
        if ($id !== null) {
            $oldData = array_merge($oldData, $model->getChildObject()->getOrigData());
        }
        $this->modelFactory->getObject('Walmart_Account_Builder')->build($model, $post->toArray());
        $id = $model->getId();

        try {
            // Add or update server
            // ---------------------------------------

            /** @var $accountObj \Ess\M2ePro\Model\Account */
            $accountObj = $model;

            if (!$accountObj->isSetProcessingLock('server_synchronize')) {

                /** @var $dispatcherObject \Ess\M2ePro\Model\Walmart\Connector\Dispatcher */
                $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

                $requestData = [
                    'title'            => $post['title'],
                    'marketplace_id'   => (int)$post['marketplace_id'],
                    'related_store_id' => (int)$post['related_store_id']
                ];

                if ($post['marketplace_id'] == \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA) {
                    $requestData['consumer_id'] = $post['consumer_id'];
                    $requestData['private_key'] = $post['private_key'];
                } else {
                    $requestData['client_id'] = $post['client_id'];
                    $requestData['client_secret'] = $post['client_secret'];
                }

                if (!$isEdit) {
                    $connectorObj = $dispatcherObject->getConnector(
                        'account',
                        'add',
                        'entityRequester',
                        $requestData,
                        $id
                    );
                    $dispatcherObject->process($connectorObj);
                } else {

                    $arrayDiffAssoc = array_diff_assoc($requestData, $oldData);
                    if (!empty($arrayDiffAssoc)) {
                        $connectorObj = $dispatcherObject->getConnector(
                            'account',
                            'update',
                            'entityRequester',
                            $requestData,
                            $id
                        );
                        $dispatcherObject->process($connectorObj);
                    }
                }
            }
            // ---------------------------------------
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            $error = 'The Walmart access obtaining is currently unavailable.<br/>Reason: %error_message%';
            $error = $this->__($error, $exception->getMessage());

            $model->delete();

            if ($this->isAjax()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $error
                ]);
                return $this->getResult();
            }

            $this->messageManager->addError($error);

            return $this->_redirect('*/walmart_account');
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true
            ]);
            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Account was saved'));

        /** @var $wizardHelper \Ess\M2ePro\Helper\Module\Wizard */
        $wizardHelper = $this->getHelper('Module\Wizard');

        $routerParams = [
            'id' => $id,
            '_current' => true
        ];
        if ($wizardHelper->isActive(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK) &&
            $wizardHelper->getStep(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK) == 'account') {
            $routerParams['wizard'] = true;
        }

        return $this->_redirect($this->getHelper('Data')->getBackUrl('list', [], ['edit'=>$routerParams]));
    }

    //########################################

    protected function getExistsAccount($search, $value)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $account */
        $account = $this->walmartFactory->getObject('Account')->getCollection()
            ->addFieldToFilter($search, $value);

        if (!$account->getSize()) {
            return null;
        }

        return $account->getFirstItem();
    }

    //########################################
}
