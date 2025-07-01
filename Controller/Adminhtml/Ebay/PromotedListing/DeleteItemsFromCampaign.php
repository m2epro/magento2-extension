<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\PromotedListing;

class DeleteItemsFromCampaign extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    private \Ess\M2ePro\Model\Ebay\PromotedListing\DeleteItemsFromCampaign $deleteItemsFromCampaign;
    private \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\PromotedListing\DeleteItemsFromCampaign $deleteItemsFromCampaign,
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign\Repository $campaignRepository,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);
        $this->deleteItemsFromCampaign = $deleteItemsFromCampaign;
        $this->campaignRepository = $campaignRepository;
    }

    public function execute()
    {
        try {
            $campaign = $this->getCampaignFromRequest();
            $this->deleteItemsFromCampaign->execute(
                $campaign,
                $this->getListingProductIdsFromRequest()
            );

            $this->setJsonContent([
                'result' => true,
                'message' => (string)__('Items were deleted from the campaign "%campaign_name"', [
                    'campaign_name' => $campaign->getName(),
                ]),
            ]);
        } catch (\Throwable $exception) {
            $this->setJsonContent([
                'result' => false,
                'fail_message' => $exception->getMessage(),
            ]);
        }

        return $this->getResult();
    }

    private function getCampaignFromRequest(): \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign
    {
        return $this->campaignRepository
            ->get((int)$this->getRequest()->getParam('campaign_id'));
    }

    private function getListingProductIdsFromRequest(): array
    {
        $listingProductIds = $this->getRequest()->getParam('listing_product_ids');
        if (empty($listingProductIds)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Listing product ids required');
        }

        return array_map(function ($listingProductId) {
            return (int)$listingProductId;
        }, $listingProductIds);
    }
}
