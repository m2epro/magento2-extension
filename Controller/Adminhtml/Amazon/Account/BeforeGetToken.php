<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\BeforeGetToken
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
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperException = $helperException;
        $this->helperDataSession = $helperDataSession;
    }

    public function execute()
    {
        // Get and save form data
        // ---------------------------------------
        $accountId = $this->getRequest()->getParam('id', 0);
        $accountTitle = $this->getRequest()->getParam('title', '');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id', 0);
        // ---------------------------------------

        $marketplace = $this->activeRecordFactory->getObjectLoaded('Marketplace', $marketplaceId);

        try {
            $backUrl = $this->getUrl('*/*/afterGetToken', ['_current' => true]);

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
            $this->helperException->process($exception);
            $error = 'The Amazon token obtaining is currently unavailable.<br/>Reason: %error_message%';
            $error = $this->__($error, $exception->getMessage());

            $this->setJsonContent([
                'message' => $error
            ]);
            return $this->getResult();
        }

        $this->helperDataSession->setValue('account_id', $accountId);
        $this->helperDataSession->setValue('account_title', $accountTitle);
        $this->helperDataSession->setValue('marketplace_id', $marketplaceId);

        $this->getResponse()->setRedirect($response['url']);
        return $this->getResponse();

        // ---------------------------------------
    }
}
