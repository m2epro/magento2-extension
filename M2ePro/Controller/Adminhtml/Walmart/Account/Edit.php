<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Account\Edit
 */
class Edit extends Account
{
    //########################################

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
            $account = $this->walmartFactory->getObjectLoaded('Account', $id);
        } catch (\Exception $e) {
        }

        if ($id && !$account->getId()) {
            $this->messageManager->addError($this->__('Account does not exist.'));
            return $this->_redirect('*/walmart_account');
        }

        $marketplaces = $this->getHelper('Component\Walmart')->getMarketplacesAvailableForApiCreation();
        if ($marketplaces->getSize() <= 0) {
            $message = 'You should select and update at least one Walmart marketplace.';
            $this->messageManager->addError($this->__($message));
            return $this->_redirect('*/walmart_account');
        }

        if ($account !== null) {
            $this->addLicenseMessage($account);
        }

        $this->getHelper('Data\GlobalData')->setValue('edit_account', $account);

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

        $this->addLeft($this->createBlock('Walmart_Account_Edit_Tabs'));
        $this->addContent($this->createBlock('Walmart_Account_Edit'));
        $this->setPageHelpLink('x/XgBhAQ');

        return $this->getResultPage();
    }

    private function addLicenseMessage(\Ess\M2ePro\Model\Account $account)
    {
        try {
            $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('account', 'get', 'info', [
                'account' => $account->getChildObject()->getServerHash(),
                'channel' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            ]);

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();
        } catch (\Exception $e) {
            return '';
        }

        if (!isset($response['info']['status']) || empty($response['info']['note'])) {
            return;
        }

        $status = (bool)$response['info']['status'];
        $note   = $response['info']['note'];

        if ($status) {
            $this->addExtendedNoticeMessage($note);
            return;
        }

        $errorMessage = $this->__(
            'Work with this Account is currently unavailable for the following reason: <br/> %error_message%',
            ['error_message' => $note]
        );

        $this->addExtendedErrorMessage($errorMessage);
        return 'MagentoMessageObj.addError(\''.$errorMessage.'\');';
    }

    //########################################
}
