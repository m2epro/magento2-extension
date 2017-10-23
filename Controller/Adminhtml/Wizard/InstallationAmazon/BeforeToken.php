<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

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

            $dispatcherObject = $this->modelFactory->getObject('Amazon\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('account','get','authUrl',
                array('back_url' => $backUrl, 'marketplace' => $marketplace->getData('native_id')));

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();

        } catch (\Exception $exception) {

            $this->getHelper('Module\Exception')->process($exception);
            // M2ePro_TRANSLATIONS
            // The Amazon token obtaining is currently unavailable.<br/>Reason: %error_message%
            $error = 'The Amazon token obtaining is currently unavailable.<br/>Reason: %error_message%';
            $error = $this->__($error, $exception->getMessage());

            $this->setJsonContent(array(
                'message' => $error
            ));
            return $this->getResult();
        }

        $this->getHelper('Data\Session')->setValue('marketplace_id', $marketplaceId);

        $this->setJsonContent(array(
            'url' => $response['url']
        ));
        return $this->getResult();

        // ---------------------------------------
    }
}