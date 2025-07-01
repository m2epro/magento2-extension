<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\PromotedListing;

class RefreshCampaigns extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    private \Ess\M2ePro\Model\Ebay\PromotedListing\RefreshCampaigns $refreshCampaigns;
    private \Ess\M2ePro\Model\Ebay\Account\Repository $ebayAccountRepository;
    private \Ess\M2ePro\Model\Ebay\Marketplace\Repository $ebayMarketplaceRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\PromotedListing\RefreshCampaigns $refreshCampaigns,
        \Ess\M2ePro\Model\Ebay\Account\Repository $ebayAccountRepository,
        \Ess\M2ePro\Model\Ebay\Marketplace\Repository $ebayMarketplaceRepository,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->refreshCampaigns = $refreshCampaigns;
        $this->ebayAccountRepository = $ebayAccountRepository;
        $this->ebayMarketplaceRepository = $ebayMarketplaceRepository;
    }

    public function execute()
    {
        try {
            $this->refreshCampaigns->execute(
                $this->getEbayAccountFromRequest(),
                $this->getEbayMarketplaceFromRequest(),
            );

            $this->setJsonContent(['result' => true]);
        } catch (\Throwable $exception) {
            $this->setJsonContent([
                'result' => false,
                'fail_message' => $exception->getMessage(),
            ]);
        }

        return $this->getResult();
    }

    private function getEbayAccountFromRequest(): \Ess\M2ePro\Model\Ebay\Account
    {
        return $this->ebayAccountRepository
            ->getByAccountId((int)$this->getRequest()->getParam('account_id'));
    }

    private function getEbayMarketplaceFromRequest(): \Ess\M2ePro\Model\Ebay\Marketplace
    {
        return $this->ebayMarketplaceRepository
            ->getByMarketplaceId((int)$this->getRequest()->getParam('marketplace_id'));
    }
}
