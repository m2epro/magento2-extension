<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\Check
 */
class Check extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $id);

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
        $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');

        /** @var \Ess\M2ePro\Model\Amazon\Connector\Account\Check\EntityRequester $connectorObj */
        $connectorObj = $dispatcherObject->getConnector(
            'account',
            'check',
            'entityRequester',
            ['account_server_hash' => $account->getChildObject()->getServerHash()]
        );
        $dispatcherObject->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        if ($responseData['status']) {
            $this->messageManager->addSuccess($this->__('Amazon account token is valid.'));
        } else {
            $this->messageManager->addError($this->__('Amazon account token is invalid. Please re-get token.'));
        }

        $this->_forward('edit');
    }
}
