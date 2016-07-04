<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

class BeforeToken extends InstallationEbay
{
     public function execute()
     {
         $accountMode = $this->getRequest()->getParam('mode');

         if (is_null($accountMode)) {
             $this->setJsonContent(array(
                 'message' => 'Account type have not been specified.'
             ));
             return $this->getResult();
         }

         try {

             $backUrl = $this->getUrl('*/*/afterToken', array('mode' => $accountMode));

             $dispatcherObject = $this->modelFactory->getObject('Ebay\Connector\Dispatcher');
             $connectorObj = $dispatcherObject->getVirtualConnector('account','get','authUrl',
                 array('back_url' => $backUrl,
                     'mode' => $accountMode),
                 NULL,NULL,NULL);

             $dispatcherObject->process($connectorObj);
             $response = $connectorObj->getResponseData();

         } catch (\Exception $exception) {
             $this->setJsonContent(array(
                 'url' => null
             ));
             return $this->getResult();
         }

         if (!$response || !isset($response['url'],$response['session_id'])) {
             $this->setJsonContent(array(
                 'url' => null
             ));
             return $this->getResult();
         }

         $this->getHelper('Data\Session')->setValue('token_session_id', $response['session_id']);

         $this->setJsonContent(array(
             'url' => $response['url']
         ));
         return $this->getResult();

         // ---------------------------------------
     }
}