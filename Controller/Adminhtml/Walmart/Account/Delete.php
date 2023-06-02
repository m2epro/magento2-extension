<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Account;

class Delete extends Account
{
    /** @var \Ess\M2ePro\Model\Walmart\Account\DeleteManager $walmartAccountDeleteManager */
    private $walmartAccountDeleteManager;

    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\Walmart\Account\DeleteManager $walmartAccountDeleteManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->walmartAccountDeleteManager = $walmartAccountDeleteManager;
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
            $this->walmartAccountDeleteManager->process($account);

            $this->messageManager->addSuccess(__('Account was deleted.'));
        } catch (\Exception $exception) {
            $this->messageManager->addError(__($exception->getMessage()));
        }

        $this->_redirect('*/*/index');
    }
}
