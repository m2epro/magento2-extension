<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Promotion;

class SynchronizePromotions extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Template
{
    private \Ess\M2ePro\Model\Ebay\Promotion\Synchronization $promotionsSynchronization;
    private \Ess\M2ePro\Model\AccountFactory $accountFactory;
    private \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory;

    public function __construct(
        \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory,
        \Ess\M2ePro\Model\AccountFactory $accountFactory,
        \Ess\M2ePro\Model\Ebay\Promotion\Synchronization $promotionsSynchronization,
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($templateManager, $ebayFactory, $context);

        $this->promotionsSynchronization = $promotionsSynchronization;
        $this->accountFactory = $accountFactory;
        $this->marketplaceFactory = $marketplaceFactory;
    }

    public function execute()
    {
        $accountId = (int)$this->getRequest()->getParam('account_id');
        $marketplaceId = (int)$this->getRequest()->getParam('marketplace_id');

        if (empty($accountId) || empty($marketplaceId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        $account = $this->accountFactory->create()->load($accountId);
        $marketplace = $this->marketplaceFactory->create()->load($marketplaceId);

        if ($account === null || $marketplace === null) {
            $this->setAjaxContent('Account or Marketplace not found.', false);

            return $this->getResult();
        }

        $this->promotionsSynchronization->process($account->getChildObject(), $marketplace);

        return $this->getResult();
    }
}
