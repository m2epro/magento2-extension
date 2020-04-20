<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Account\CheckAuth
 */
class CheckAuth extends Account
{
    public function execute()
    {
        $consumerId    = $this->getRequest()->getParam('consumer_id', false);
        $oldPrivateKey = $this->getRequest()->getParam('old_private_key', false);
        $clientId      = $this->getRequest()->getParam('client_id', false);
        $clientSecret  = $this->getRequest()->getParam('client_secret', false);
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', false);

        $result =  [
            'result' => false,
            'reason' => null
        ];

        /** @var $marketplaceObject \Ess\M2ePro\Model\Marketplace */
        $marketplaceObject = $this->walmartFactory->getCachedObjectLoaded(
            'Marketplace',
            $marketplaceId
        );

        if ($marketplaceId == \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA &&
            $consumerId && $oldPrivateKey) {
            $requestData = [
                'marketplace' => $marketplaceObject->getNativeId(),
                'consumer_id' => $consumerId,
                'private_key' => $oldPrivateKey,
            ];
        } elseif ($marketplaceId != \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA &&
            $clientId && $clientSecret) {
            $requestData = [
                'marketplace'   => $marketplaceObject->getNativeId(),
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'consumer_id'   => $consumerId
            ];
        } else {
            $this->setJsonContent($result);
            return $this->getResult();
        }

        try {
            $dispatcherObject = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('account', 'check', 'access', $requestData);
            $dispatcherObject->process($connectorObj);

            $response = $connectorObj->getResponseData();

            $result['result'] = isset($response['status']) ? $response['status']  : null;

            if (!empty($response['reason'])) {
                $result['reason'] = $this->getHelper('Data')->escapeJs($response['reason']);
            }
        } catch (\Exception $exception) {
            $result['result'] = false;
            $this->getHelper('Module\Exception')->process($exception);
        }

        $this->setJsonContent($result);

        return $this->getResult();
    }
}
