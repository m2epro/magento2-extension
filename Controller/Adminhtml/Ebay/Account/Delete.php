<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;
use Ess\M2ePro\Model\Ebay\Account\Issue\ValidTokens;

class Delete extends Account
{
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $permanentCacheHelper;

    /** @var \Ess\M2ePro\Model\Ebay\Account\DeleteManager $ebayAccountDeleteManager */
    private $ebayAccountDeleteManager;

    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\Ebay\Account\DeleteManager $ebayAccountDeleteManager,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);

        $this->permanentCacheHelper = $permanentCacheHelper;
        $this->ebayAccountDeleteManager = $ebayAccountDeleteManager;
        $this->accountCollectionFactory = $accountCollectionFactory;
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute(): void
    {
        $id = $this->getRequest()->getParam('id');

        $accountCollection = $this->accountCollectionFactory->create();

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $accountCollection->addFieldToFilter('id', $id)
                                     ->getFirstItem();

        if (!$account->getId()) {
            $this->messageManager->addError(__('Account is not found and cannot be deleted.'));

            $this->_redirect('*/*/index');

            return;
        }

        try {
            $this->ebayAccountDeleteManager->process($account);

            $this->permanentCacheHelper->removeValue(ValidTokens::ACCOUNT_TOKENS_CACHE_KEY);

            $this->messageManager->addSuccess(__('Account was deleted.'));
        } catch (\Exception $exception) {
            $this->messageManager->addError(__($exception->getMessage()));
        }

        $this->_redirect('*/*/index');
    }
}
