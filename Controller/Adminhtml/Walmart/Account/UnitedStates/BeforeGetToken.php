<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account\UnitedStates;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

class BeforeGetToken extends Account
{
    private \Ess\M2ePro\Helper\Module\Exception $helperException;
    private \Ess\M2ePro\Model\Walmart\Connector\Account\GetGrantAccessUrl\Processor $connectProcessor;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Connector\Account\GetGrantAccessUrl\Processor $connectProcessor,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->helperException = $helperException;
        $this->connectProcessor = $connectProcessor;
    }

    public function execute(): void
    {
        $accountId = (int)$this->getRequest()->getParam('id');
        $specificEndUrl = $this->getRequest()->getParam('specific_end_url');

        try {
            $backUrl = $this->getUrl('*/walmart_account_unitedStates/afterGetToken', [
                'id' => $accountId,
                'specific_end_url' => $specificEndUrl,
                '_current' => true,
            ]);

            $response = $this->connectProcessor->process($backUrl);
        } catch (\Throwable $throwable) {
            $this->helperException->process($throwable);
            $error = (string)__(
                'The Walmart token obtaining is currently unavailable.<br/>Reason: %error_message',
                ['error_message' => $throwable->getMessage()]
            );

            $this->messageManager->addError($error);

            $this->_redirect('*/walmart_account');

            return;
        }

        $this->_redirect($response->getUrl());
    }
}
