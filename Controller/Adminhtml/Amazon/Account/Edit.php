<?php

/**
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
        $id = (int)$this->getRequest()->getParam('id', 0);

        $account = null;
        try {
            /** @var \Ess\M2ePro\Model\Account $account */
            $account = $this->amazonFactory->getObjectLoaded('Account', $id);
        } catch (\Exception $e) {
        }

        if ($account === null || !$account->getId()) {
            $this->messageManager->addErrorMessage($this->__('Account does not exist.'));

            return $this->_redirect('*/amazon_account');
        }

        $this->addLicenseMessage($account);

        $this->helperDataGlobalData->setValue('edit_account', $account);

        // Set header text
        // ---------------------------------------

        $headerText = $this->__('Edit Account') . ' "' . $this->helperData->escapeHtml($account->getTitle()) . '"';
        $this->getResultPage()->getConfig()->getTitle()->prepend($headerText);

        // ---------------------------------------

        $this->addLeft($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs::class));
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit::class));
        $this->setPageHelpLink('accounts');

        return $this->getResultPage();
    }

    private function addLicenseMessage(\Ess\M2ePro\Model\Account $account)
    {
        try {
            /** @var \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher $dispatcherObject */
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
        $note = $response['info']['note'];

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
