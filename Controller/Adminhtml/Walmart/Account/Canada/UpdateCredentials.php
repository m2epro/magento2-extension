<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account\Canada;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

class UpdateCredentials extends Account
{
    private \Ess\M2ePro\Helper\Module\Exception $helperException;
    private \Ess\M2ePro\Model\Walmart\Account\Canada\Update $accountUpdate;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Account\Canada\Update $accountUpdate,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->accountUpdate = $accountUpdate;
        $this->helperException = $helperException;
    }

    public function execute()
    {
        $consumerId = $this->getRequest()->getPost('consumer_id');
        $privateKey = $this->getRequest()->getPost('private_key');
        $accountId = (int)$this->getRequest()->getParam('id');

        if (empty($consumerId) || empty($privateKey)) {
            $this->messageManager->addErrorMessage(
                (string)__('Please complete all required fields before saving the configurations.')
            );

            $this->setJsonContent(
                [
                    'result' => false,
                    'redirectUrl' => $this->getUrl('*/walmart_account/edit', ['id' => $accountId])
                ]
            );

            return $this->getResult();
        }

        try {
            $this->accountUpdate->updateAccount($consumerId, $privateKey, $accountId);
        } catch (\Throwable $throwable) {
            $this->helperException->process($throwable);

            $message = (string)__(
                'The Walmart access obtaining is currently unavailable. Reason: %error_message',
                ['error_message' => $throwable->getMessage()]
            );

            $this->messageManager->addErrorMessage($message);

            $this->setJsonContent(
                [
                    'result' => false,
                    'redirectUrl' => $this->getUrl('*/walmart_account/edit', ['id' => $accountId])
                ]
            );

            return $this->getResult();
        }

        $this->messageManager->addSuccessMessage(__('Access Details were updated'));
        $this->setJsonContent(
            [
                'result' => true,
                'redirectUrl' => $this->getUrl('*/walmart_account/edit', ['id' => $accountId])
            ]
        );

        return $this->getResult();
    }
}
