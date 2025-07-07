<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account\Canada;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

class Create extends Account
{
    private \Ess\M2ePro\Helper\Module\Exception $helperException;
    private \Ess\M2ePro\Model\Walmart\Account\Canada\Create $accountCreate;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Account\Canada\Create $accountCreate,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->accountCreate = $accountCreate;
        $this->helperException = $helperException;
    }

    public function execute()
    {
        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        $consumerId = $this->getRequest()->getPost('consumer_id');
        $privateKey = $this->getRequest()->getPost('private_key');
        $title = $this->getRequest()->getPost('title');
        $specificEndUrl = $this->getRequest()->getPost('specific_end_url');

        if (empty($consumerId) || empty($privateKey)) {
            $this->messageManager->addErrorMessage(
                (string)__('Please complete all required fields before saving the configurations.')
            );
            $this->setJsonContent(
                [
                    'result' => false,
                    'redirectUrl' => $this->_redirect('*/walmart_account/index')
                ]
            );

            return $this->getResult();
        }

        try {
            $account = $this->accountCreate->createAccount($marketplaceId, $consumerId, $privateKey, $title);
        } catch (\Throwable $throwable) {
            $this->helperException->process($throwable);

            $message = (string)__(
                'The Walmart access obtaining is currently unavailable. Reason: %error_message',
                ['error_message' => $throwable->getMessage()]
            );

            $this->messageManager->addErrorMessage($message);

            if ($specificEndUrl !== null) {
                return $this->_redirect($specificEndUrl);
            }

            return $this->_redirect('*/walmart_account/index');
        }

        $this->messageManager->addSuccessMessage((string)__('Account was saved'));

        if ($specificEndUrl !== null) {
            return $this->_redirect($specificEndUrl);
        }

        return $this->_redirect('*/walmart_account/edit', ['id' => $account->getId(), '_current' => true]);
    }
}
