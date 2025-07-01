<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing\Campaign;

use Ess\M2ePro\Model\ResourceModel\Ebay\PromotedListing\Campaign as CampaignResource;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Ebay\PromotedListing\Campaign\CollectionFactory $collectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\PromotedListing\Campaign */
    private CampaignResource $campaignResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\PromotedListing\Campaign\CollectionFactory $collectionFactory,
        CampaignResource $campaignResource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->campaignResource = $campaignResource;
    }

    public function get(int $id): \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(CampaignResource::COLUMN_ID, ['eq' => $id]);

        $campaign = $collection->getFirstItem();
        if ($campaign->isObjectNew()) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf('Not found Campaign by id %s', $id)
            );
        }

        return $campaign;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign[]
     */
    public function getAllByAccountId(int $accountId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            CampaignResource::COLUMN_ACCOUNT_ID,
            ['eq' => $accountId]
        );

        return array_values($collection->getItems());
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign[]
     */
    public function getAllByAccountIdAndMarketplaceId(int $accountId, int $marketplaceId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            CampaignResource::COLUMN_ACCOUNT_ID,
            ['eq' => $accountId]
        );
        $collection->addFieldToFilter(
            CampaignResource::COLUMN_MARKETPLACE_ID,
            ['eq' => $marketplaceId]
        );

        return array_values($collection->getItems());
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign[]
     */
    public function getExceptForIds(int $accountId, ?int $marketplaceId, array $exceptIds): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            CampaignResource::COLUMN_ACCOUNT_ID,
            ['eq' => $accountId]
        );

        if (!empty($marketplaceId)) {
            $collection->addFieldToFilter(
                CampaignResource::COLUMN_MARKETPLACE_ID,
                ['eq' => $marketplaceId]
            );
        }

        $collection->addFieldToFilter(
            CampaignResource::COLUMN_EBAY_CAMPAIGN_ID,
            ['nin' => $exceptIds]
        );

        return array_values($collection->getItems());
    }

    public function delete(\Ess\M2ePro\Model\Ebay\PromotedListing\Campaign $campaign)
    {
        $this->campaignResource->delete($campaign);
    }

    public function create(\Ess\M2ePro\Model\Ebay\PromotedListing\Campaign $campaign)
    {
        $campaign->isObjectCreatingState(true);
        $this->campaignResource->save($campaign);
    }

    public function save($campaign)
    {
        $this->campaignResource->save($campaign);
    }
}
