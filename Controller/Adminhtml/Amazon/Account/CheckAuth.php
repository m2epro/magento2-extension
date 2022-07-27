<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class CheckAuth extends Account
{
    /** @var \Ess\M2ePro\Helper\Module */
    private $helperModule;

    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;

    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    public function __construct(
        \Ess\M2ePro\Helper\Module $helperModule,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperModule = $helperModule;
        $this->helperException = $helperException;
        $this->helperData = $helperData;
    }

    public function execute()
    {
        if (!$this->helperModule->isProductionEnvironment()) {
            $this->setJsonContent(
                [
                    'result' => true,
                    'reason' => null
                ]
            );

            return $this->getResult();
        }

        $merchantId = $this->getRequest()->getParam('merchant_id');
        $token = $this->getRequest()->getParam('token');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        $result = [
            'result' => false,
            'reason' => null
        ];

        if ($merchantId && $token && $marketplaceId) {
            $marketplaceNativeId = $this->amazonFactory
                ->getCachedObjectLoaded('Marketplace', $marketplaceId)
                ->getNativeId();

            $params = [
                'marketplace' => $marketplaceNativeId,
                'merchant_id' => $merchantId,
                'token'       => $token,
            ];

            try {
                $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
                $connectorObj = $dispatcherObject->getVirtualConnector('account', 'check', 'access', $params);
                $dispatcherObject->process($connectorObj);

                $response = $connectorObj->getResponseData();

                $result['result'] = isset($response['status']) ? $response['status']
                    : null;
                if (isset($response['reason'])) {
                    $result['reason'] = $this->helperData->escapeJs($response['reason']);
                }
            } catch (\Exception $exception) {
                $result['result'] = false;
                $this->helperException->process($exception);
            }
        }

        $this->setJsonContent($result);

        return $this->getResult();
    }
}
