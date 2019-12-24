<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay\BeforeToken
 */
class BeforeToken extends InstallationEbay
{
    public function execute()
    {
        $accountMode = $this->getRequest()->getParam('mode');

        if ($accountMode === null) {
            $this->setJsonContent([
                'message' => 'Account type have not been specified.'
            ]);
            return $this->getResult();
        }

        try {
            $backUrl = $this->getUrl('*/*/afterToken', ['mode' => $accountMode]);

            $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'account',
                'get',
                'authUrl',
                ['back_url' => $backUrl,
                    'mode' => $accountMode],
                null,
                null,
                null
            );

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);
            // M2ePro_TRANSLATIONS
            // The eBay token obtaining is currently unavailable.<br/>Reason: %error_message%
            $error = 'The eBay token obtaining is currently unavailable.<br/>Reason: %error_message%';
            $error = $this->__($error, $exception->getMessage());

            $this->setJsonContent([
                'type'    => 'error',
                'message' => $error
            ]);

            return $this->getResult();
        }

        if (!$response || !isset($response['url'], $response['session_id'])) {
            $this->setJsonContent([
                'url' => null
            ]);
            return $this->getResult();
        }

        $this->getHelper('Data\Session')->setValue('token_session_id', $response['session_id']);

        $this->setJsonContent([
            'url' => $response['url']
        ]);
        return $this->getResult();

        // ---------------------------------------
    }
}
