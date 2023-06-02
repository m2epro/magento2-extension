<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

class Delete extends Account
{
    /** @var \Ess\M2ePro\Model\Amazon\Account\DeleteManager $amazonAccountDeleteManager */
    private $amazonAccountDeleteManager;

    /** @var \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory */
    private $accountCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Account\CollectionFactory $accountCollectionFactory,
        \Ess\M2ePro\Model\Amazon\Account\DeleteManager $amazonAccountDeleteManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->amazonAccountDeleteManager = $amazonAccountDeleteManager;
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
            $this->amazonAccountDeleteManager->process($account);

            $this->messageManager->addSuccess(__('Account was deleted.'));
        } catch (\Exception $exception) {
            $this->messageManager->addError(__($exception->getMessage()));
        }

        $this->_redirect('*/*/index');
    }
}
