<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\Edit
 */
class Edit extends Account
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $helperAmazon;

    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $helperDataGlobalData;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon $helperAmazon,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Data\GlobalData $helperDataGlobalData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperAmazon = $helperAmazon;
        $this->helperData = $helperData;
        $this->helperDataGlobalData = $helperDataGlobalData;
    }

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
        } catch (\Exception $e) {
        }

        if ($id && !$account->getId()) {
            $this->messageManager->addError($this->__('Account does not exist.'));
            return $this->_redirect('*/amazon_account');
        }

        $marketplaces = $this->helperAmazon->getMarketplacesAvailableForApiCreation();
        if ($marketplaces->getSize() <= 0) {
            $message = 'You should select and update at least one Amazon marketplace.';
            $this->messageManager->addError($this->__($message));
            return $this->_redirect('*/amazon_account');
        }

        if ($account !== null) {
            $this->addLicenseMessage($account);
        }

        $this->helperDataGlobalData->setValue('edit_account', $account);

        // Set header text
        // ---------------------------------------

        $headerTextEdit = $this->__('Edit Account');
        $headerTextAdd = $this->__('Add Account');

        if ($account &&
            $account->getId()
        ) {
            $headerText = $headerTextEdit;
            $headerText .= ' "'.$this->helperData->escapeHtml($account->getTitle()).'"';
        } else {
            $headerText = $headerTextAdd;
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);

        // ---------------------------------------

        $this->addLeft($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs::class));
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit::class));
        $this->setPageHelpLink('x/Hv8UB');

        return $this->getResultPage();
    }

    private function addLicenseMessage(\Ess\M2ePro\Model\Account $account)
    {
        try {
            $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('account', 'get', 'info', [
                'account' => $account->getChildObject()->getServerHash(),
                'channel' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
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
    }
}
