<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class Edit extends Account
{
    protected function getLayoutType()
    {
        return self::LAYOUT_TWO_COLUMNS;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        $account = null;
        try {
            /** @var \Ess\M2ePro\Model\Account $account */
            $account = $this->amazonFactory->getObjectLoaded('Account', $id);
        } catch (\Exception $e) {}

        if ($id && !$account->getId()) {
            $this->messageManager->addError($this->__('Account does not exist.'));
            return $this->_redirect('*/amazon_account');
        }

        $marketplaces = $this->getHelper('Component\Amazon')->getMarketplacesAvailableForApiCreation();
        if ($marketplaces->getSize() <= 0) {
            $message = 'You should select and update at least one Amazon marketplace.';
            $this->messageManager->addError($this->__($message));
            return $this->_redirect('*/amazon_account');
        }

        if ($id) {
            $this->getHelper('Data\GlobalData')->setValue('license_message', $this->getLicenseMessage($account));
        }

        $this->getHelper('Data\GlobalData')->setValue('temp_data', $account);

        // Set header text
        // ---------------------------------------

        $headerTextEdit = $this->__('Edit Account');
        $headerTextAdd = $this->__('Add Account');

        if ($account &&
            $account->getId()
        ) {
            $headerText = $headerTextEdit;
            $headerText .= ' "'.$this->getHelper('Data')->escapeHtml($account->getTitle()).'"';
        } else {
            $headerText = $headerTextAdd;
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);
        
        // ---------------------------------------

        $this->addLeft($this->createBlock('Amazon\Account\Edit\Tabs'));
        $this->addContent($this->createBlock('Amazon\Account\Edit'));
        $this->setComponentPageHelpLink('Accounts');

        return $this->getResultPage();
    }

    private function getLicenseMessage(\Ess\M2ePro\Model\Account $account)
    {
        try {
            $dispatcherObject = $this->modelFactory->getObject('Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('account','get','info', array(
                'account' => $account->getChildObject()->getServerHash(),
                'channel' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            ));

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();
        } catch (\Exception $e) {
            return '';
        }

        if (!isset($response['info']['status']) || empty($response['info']['note'])) {
            return '';
        }

        $status = (bool)$response['info']['status'];
        $note   = $response['info']['note'];

        if ($status) {
            return 'MagentoMessageObj.addNotice(\''.$note.'\');';
        }

        $errorMessage = $this->__(
            'Work with this Account is currently unavailable for the following reason: <br/> %error_message%',
            array('error_message' => $note)
        );

        return 'MagentoMessageObj.addError(\''.$errorMessage.'\');';
    }
}