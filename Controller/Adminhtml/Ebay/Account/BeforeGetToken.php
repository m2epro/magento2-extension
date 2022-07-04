<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\BeforeGetToken
 */
class BeforeGetToken extends Account
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;

    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $helperDataSession;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data\Session $helperDataSession,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);

        $this->helperException = $helperException;
        $this->helperDataSession = $helperDataSession;
    }

    public function execute()
    {
        // Get and save form data
        // ---------------------------------------
        $accountId = $this->getRequest()->getParam('id', 0);
        $accountTitle = $this->getRequest()->getParam('title', '');
        $accountMode = (int)$this->getRequest()->getParam('mode', \Ess\M2ePro\Model\Ebay\Account::MODE_SANDBOX);
        // ---------------------------------------

        // Get and save session id
        // ---------------------------------------
        $mode = $accountMode == \Ess\M2ePro\Model\Ebay\Account::MODE_PRODUCTION ? 'production' : 'sandbox';

        try {
            $backUrl = $this->getUrl('*/*/afterGetToken', ['_current' => true]);

            $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'account',
                'get',
                'grandAccessUrl',
                ['back_url' => $backUrl, 'mode' => $mode],
                null,
                null,
                null,
                $mode
            );

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();
        } catch (\Exception $exception) {
            $this->helperException->process($exception);
            $error = 'The eBay token obtaining is currently unavailable.<br/>Reason: %error_message%';
            $error = $this->__($error, $exception->getMessage());

            $this->messageManager->addError($error);

            $this->_redirect($this->getUrl('*/*/index'));
            return;
        }

        $this->helperDataSession->setValue('get_token_account_id', $accountId);
        $this->helperDataSession->setValue('get_token_account_title', $accountTitle);
        $this->helperDataSession->setValue('get_token_account_mode', $accountMode);
        $this->helperDataSession->setValue('get_token_session_id', $response['session_id']);

        $this->_redirect($response['url']);
        // ---------------------------------------
    }
}
