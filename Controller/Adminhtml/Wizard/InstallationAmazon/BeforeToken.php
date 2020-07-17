<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon\BeforeToken
 */
class BeforeToken extends InstallationAmazon
{
    public function execute()
    {
        // Get and save form data
        // ---------------------------------------
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', 0);
        // ---------------------------------------

        $marketplace = $this->activeRecordFactory->getObjectLoaded('Marketplace', $marketplaceId);

        try {
            $backUrl = $this->getUrl('*/*/afterGetTokenAutomatic');

            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'account',
                'get',
                'authUrl',
                ['back_url' => $backUrl, 'marketplace' => $marketplace->getData('native_id')]
            );

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
                $this->modelFactory->getObject('Servicing_Task_License')->getPublicNick()
            );

            $error = 'The Amazon token obtaining is currently unavailable.<br/>Reason: %error_message%';

            if (!$this->getHelper('Module\License')->isValidDomain() ||
                !$this->getHelper('Module\License')->isValidIp()) {
                $error .= '</br>Go to the <a href="%url%" target="_blank">License Page</a>.';
                $error = $this->__(
                    $error,
                    $exception->getMessage(),
                    $this->getHelper('View\Configuration')->getLicenseUrl(['wizard' => 1])
                );
            } else {
                $error = $this->__($error, $exception->getMessage());
            }

            $this->setJsonContent([
                'message' => $error
            ]);
            return $this->getResult();
        }

        $this->getHelper('Data\Session')->setValue('marketplace_id', $marketplaceId);

        $this->setJsonContent([
            'url' => $response['url']
        ]);
        return $this->getResult();

        // ---------------------------------------
    }
}
