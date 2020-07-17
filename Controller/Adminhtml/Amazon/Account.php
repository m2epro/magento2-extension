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
abstract class Account extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::amazon_configuration_accounts');
    }

    //########################################

    protected function updateAccount($id, $data)
    {
        $model = $this->amazonFactory->getObject('Account');

        if (isset($id)) {
            $model->load($id);
            $model->setData('isEdit', true);
        } else {
            $model->setData('isEdit', false);
        }

        $this->modelFactory->getObject('Amazon_Account_Builder')->build($model, $data);

        return $model;
    }

    protected function sendDataToServer($model)
    {
        /** @var $accountObj \Ess\M2ePro\Model\Account */
        $accountObj = $model;

        if (!$accountObj->isSetProcessingLock('server_synchronize')) {
            /** @var $dispatcherObject \Ess\M2ePro\Model\Amazon\Connector\Dispatcher */
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');

            $data = [
                'title'            => $model->getTitle(),
                'marketplace_id'   => $model->getChildObject()->getMarketplaceId(),
                'merchant_id'      => $model->getChildObject()->getMerchantId(),
                'token'            => $model->getChildObject()->getToken(),
                'related_store_id' => $model->getChildObject()->getRelatedStoreId()
            ];

            if (!$model->getData('isEdit')) {
                $connectorObj = $dispatcherObject->getConnector(
                    'account',
                    'add',
                    'entityRequester',
                    $data,
                    $model->getId()
                );
                $dispatcherObject->process($connectorObj);
            } else {
                $oldData = array_merge($model->getOrigData(), $model->getChildObject()->getOrigData());
                $params = array_diff_assoc($data, $oldData);

                if (!empty($params)) {
                    $connectorObj = $dispatcherObject->getConnector(
                        'account',
                        'update',
                        'entityRequester',
                        $params,
                        $model->getId()
                    );
                    $dispatcherObject->process($connectorObj);
                }
            }
        }
    }

    //########################################
}
