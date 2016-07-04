<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class CheckAuth extends Account
{
    public function execute()
    {
        $merchantId    = $this->getRequest()->getParam('merchant_id');
        $token         = $this->getRequest()->getParam('token');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        $result = array (
            'result' => false,
            'reason' => null
        );

        if ($merchantId && $token && $marketplaceId) {

            $marketplaceNativeId = $this->amazonFactory
                ->getCachedObjectLoaded('Marketplace', $marketplaceId)
                ->getNativeId();

            $params = array(
                'marketplace' => $marketplaceNativeId,
                'merchant_id' => $merchantId,
                'token'       => $token,
            );

            try {

                $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
                $connectorObj = $dispatcherObject->getVirtualConnector('account','check','access',$params);
                $dispatcherObject->process($connectorObj);

                $response = $connectorObj->getResponseData();

                $result['result'] = isset($response['status']) ? $response['status']
                    : null;
                if (isset($response['reason'])) {
                    $result['reason'] = $this->getHelper('Data')->escapeJs($response['reason']);
                }

            } catch (\Exception $exception) {
                $result['result'] = false;
                $this->getHelper('Module\Exception')->process($exception);
            }
        }

        $this->setJsonContent($result);

        return $this->getResult();
    }
}