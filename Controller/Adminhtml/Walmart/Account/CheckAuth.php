<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

class CheckAuth extends Account
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;

    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->helperException = $helperException;
        $this->helperData = $helperData;
    }

    public function execute()
    {
        $consumerId    = $this->getRequest()->getParam('consumer_id', false);
        $privateKey    = $this->getRequest()->getParam('private_key', false);
        $clientId      = $this->getRequest()->getParam('client_id', false);
        $clientSecret  = $this->getRequest()->getParam('client_secret', false);
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', false);

        $result =  [
            'result' => false,
            'reason' => null
        ];

        /** @var \Ess\M2ePro\Model\Marketplace $marketplaceObject */
        $marketplaceObject = $this->walmartFactory->getCachedObjectLoaded(
            'Marketplace',
            $marketplaceId
        );

        if ($marketplaceId == \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA &&
            $consumerId && $privateKey) {
            $requestData = [
                'marketplace' => $marketplaceObject->getNativeId(),
                'consumer_id' => $consumerId,
                'private_key' => $privateKey,
            ];
        } elseif ($marketplaceId != \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA &&
            $clientId && $clientSecret) {
            $requestData = [
                'marketplace'   => $marketplaceObject->getNativeId(),
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
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
                $result['reason'] = $this->helperData->escapeJs($response['reason']);
            }
        } catch (\Exception $exception) {
            $result['result'] = false;
            $this->helperException->process($exception);
        }

        $this->setJsonContent($result);

        return $this->getResult();
    }
}
