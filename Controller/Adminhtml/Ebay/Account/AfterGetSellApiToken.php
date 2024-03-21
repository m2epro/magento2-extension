<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class AfterGetSellApiToken extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    /** @var \Ess\M2ePro\Model\Ebay\Account\Create */
    private $accountCreate;
    /** @var \Ess\M2ePro\Model\Ebay\Account\Update */
    private $accountUpdate;
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Store */
    private $componentEbayCategoryStore;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Account\Update $accountUpdate,
        \Ess\M2ePro\Model\Ebay\Account\Create $accountCreate,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);
        $this->accountUpdate = $accountUpdate;
        $this->accountCreate = $accountCreate;
        $this->componentEbayCategoryStore = $componentEbayCategoryStore;
    }

    public function execute()
    {
        $authCode = base64_decode((string)$this->getRequest()->getParam('code'));

        if ($authCode === '') {
            $this->_redirect('*/*/index');
        }

        $accountId = (int)$this->getRequest()->getParam('id');
        $mode = (int)$this->getRequest()->getParam('mode');

        if ($accountId === 0) {
            try {
                $account = $this->accountCreate->create($authCode, $mode);
                $this->getMessageManager()->addSuccessMessage(__('eBay Account has been added'));
            } catch (\Throwable $exception) {
                $this->messageManager->addError($exception->getMessage());
                return $this->_redirect('*/*/index');
            }
        } else {
            try {
                $account = $this->ebayFactory->getObjectLoaded('Account', $accountId);
                $this->accountUpdate->updateCredentials($account, $authCode, $mode);
                $this->getMessageManager()->addSuccessMessage(__('OAuth Token has been updated'));
            } catch (\Throwable $exception) {
                $this->messageManager->addError($exception->getMessage());
                return $this->_redirect('*/*/edit', ['id' => $account->getId(), '_current' => true]);
            }
        }

        if ($this->componentEbayCategoryStore->isExistDeletedCategories()) {
            $url = $this->getUrl('*/ebay_category/index', ['filter' => base64_encode('state=0')]);

            $this->messageManager->addWarning(
                __(
                    'Some eBay Store Categories were deleted from eBay. Click ' .
                    '<a target="_blank" href="%1" class="external-link">here</a> to check.',
                    $url
                )
            );
        }

        $this->_redirect('*/*/edit', ['id' => $account->getId(), '_current' => true]);
    }
}
