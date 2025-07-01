<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Marketplace;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Listing $listingResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource
    ) {
        $this->marketplaceCollectionFactory = $marketplaceCollectionFactory;
        $this->listingResource = $listingResource;
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace[]
     */
    public function findMarketplacesWithExistListing(\Ess\M2ePro\Model\Account $account): array
    {
        $marketplaceCollection = $this->marketplaceCollectionFactory->createWithEbayChildMode();
        $marketplaceCollection->join(
            ['l' => $this->listingResource->getMainTable()],
            sprintf(
                'main_table.%s = l.%s',
                \Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_ID,
                \Ess\M2ePro\Model\ResourceModel\Listing::COLUMN_MARKETPLACE_ID
            ),
            []
        );

        $marketplaceCollection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_STATUS,
            \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE
        );
        $marketplaceCollection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing::COLUMN_ACCOUNT_ID,
            $account->getId()
        );

        $marketplaceCollection->getSelect()->group(\Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_ID);

        return array_values($marketplaceCollection->getItems());
    }

    public function getByMarketplaceId(int $marketplaceId): \Ess\M2ePro\Model\Ebay\Marketplace
    {
        $collection = $this->marketplaceCollectionFactory->createWithEbayChildMode();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Marketplace::COLUMN_MARKETPLACE_ID,
            ['eq' => $marketplaceId]
        );

        $marketplace = $collection->getFirstItem();
        if ($marketplace->isObjectNew()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Not found Marketplace by id ' . $marketplaceId);
        }

        return $marketplace->getChildObject();
    }

    public function getByNativeId(int $nativeId): \Ess\M2ePro\Model\Ebay\Marketplace
    {
        $collection = $this->marketplaceCollectionFactory->createWithEbayChildMode();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Marketplace::COLUMN_NATIVE_ID,
            ['eq' => $nativeId]
        );

        $marketplace = $collection->getFirstItem();
        if ($marketplace->isObjectNew()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Not found Marketplace by native_id ' . $nativeId);
        }

        return $marketplace->getChildObject();
    }
}
