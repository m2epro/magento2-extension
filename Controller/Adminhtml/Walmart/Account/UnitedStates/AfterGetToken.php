<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account\UnitedStates;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

class AfterGetToken extends Account
{
    private \Ess\M2ePro\Helper\Module\Exception $helperException;
    private \Ess\M2ePro\Model\Walmart\Account\Repository $accountRepository;
    private \Ess\M2ePro\Model\Walmart\Account\UnitedStates\Create $accountCreate;
    private \Ess\M2ePro\Model\Walmart\Account\UnitedStates\Update $accountUpdate;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Account\UnitedStates\Create $accountCreate,
        \Ess\M2ePro\Model\Walmart\Account\UnitedStates\Update $accountUpdate,
        \Ess\M2ePro\Model\Walmart\Account\Repository $accountRepository,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);
        $this->helperException = $helperException;
        $this->accountRepository = $accountRepository;
        $this->accountCreate = $accountCreate;
        $this->accountUpdate = $accountUpdate;
    }

    // ----------------------------------------

    public function execute()
    {
        $authCode = $this->getRequest()->getParam('code');
        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');
        $sellerId = $this->getRequest()->getParam('sellerId');
        /** @var string|null $clientId */
        $clientId = $this->getRequest()->getParam('clientId');
        $specificEndUrl = $this->getRequest()->getParam('specific_end_url');

        if ($authCode === null) {
            $this->_redirect('*/walmart_account/index');
        }

        $accountId = (int)$this->getRequest()->getParam('id');
        try {
            if (empty($accountId)) {
                $account = $this->accountCreate->createAccount($authCode, $marketplaceId, $sellerId, $clientId);

                if ($specificEndUrl !== null) {
                    return $this->_redirect($specificEndUrl);
                }

                return $this->_redirect(
                    '*/walmart_account/edit',
                    [
                        'id' => $account->getId(),
                        '_current' => true,
                    ],
                );
            }

            if (!$this->accountRepository->isAccountExists($accountId)) {
                throw new \LogicException('Account not found.');
            }

            $this->accountUpdate->updateAccount($authCode, $sellerId, $accountId, $clientId);
        } catch (\Throwable $throwable) {
            $this->helperException->process($throwable);

            $this->messageManager->addError(
                (string)__(
                    'The Walmart access obtaining is currently unavailable.<br/>Reason: %error_message',
                    ['error_message' => $throwable->getMessage()],
                ),
            );

            if ($specificEndUrl !== null) {
                return $this->_redirect($specificEndUrl);
            }

            return $this->_redirect('*/walmart_account/index');
        }

        $this->messageManager->addSuccessMessage((string)__('Account was saved'));

        return $this->_redirect('*/walmart_account/edit', ['id' => $accountId, '_current' => true]);
    }
}
