<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class BeforeGetToken extends Account
{
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperException = $helperException;
    }

    public function execute()
    {
        $accountId = (int)$this->getRequest()->getParam('id', 0);
        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id', 0);

        $marketplace = $this->activeRecordFactory->getObjectLoaded('Marketplace', $marketplaceId);

        try {
            $backUrl = $this->getUrl(
                '*/*/afterGetToken',
                [
                    'marketplace_id' => $marketplaceId,
                    'account_id' => $accountId,
                ]
            );

            /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getConnector(
                'account',
                'get',
                'authUrlRequester',
                ['back_url' => $backUrl, 'marketplace_native_id' => $marketplace->getData('native_id')]
            );

            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();
        } catch (\Exception $exception) {
            $this->helperException->process($exception);
            $error = 'The Amazon token obtaining is currently unavailable.<br/>Reason: %error_message%';
            $error = $this->__($error, $exception->getMessage());

            $this->setJsonContent([
                'message' => $error,
            ]);

            return $this->getResult();
        }

        $this->getResponse()->setRedirect($response['url']);

        return $this->getResponse();
    }
}
