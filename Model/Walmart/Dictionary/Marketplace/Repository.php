<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Dictionary\Marketplace;

use Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Marketplace as MarketplaceDictionaryResource;

class Repository
{
    private MarketplaceDictionaryResource $marketplaceDictionaryResource;
    private MarketplaceDictionaryResource\CollectionFactory $marketplaceDictionaryCollectionFactory;

    public function __construct(
        MarketplaceDictionaryResource $marketplaceDictionaryResource,
        MarketplaceDictionaryResource\CollectionFactory $marketplaceDictionaryCollectionFactory
    ) {
        $this->marketplaceDictionaryResource = $marketplaceDictionaryResource;
        $this->marketplaceDictionaryCollectionFactory = $marketplaceDictionaryCollectionFactory;
    }

    public function create(\Ess\M2ePro\Model\Walmart\Dictionary\Marketplace $marketplaceDictionary): void
    {
        $this->marketplaceDictionaryResource->save($marketplaceDictionary);
    }

    public function removeByMarketplace(int $marketplaceId): void
    {
        $this->marketplaceDictionaryResource
            ->getConnection()
            ->delete(
                $this->marketplaceDictionaryResource->getMainTable(),
                [MarketplaceDictionaryResource::COLUMN_MARKETPLACE_ID . ' = ?' => $marketplaceId]
            );
    }

    public function findByMarketplaceId(int $marketplaceId): ?\Ess\M2ePro\Model\Walmart\Dictionary\Marketplace
    {
        $collection = $this->marketplaceDictionaryCollectionFactory->create();
        $collection->addFieldToFilter(MarketplaceDictionaryResource::COLUMN_MARKETPLACE_ID, $marketplaceId);

        /** @var \Ess\M2ePro\Model\Walmart\Dictionary\Marketplace $dictionary */
        $dictionary = $collection->getFirstItem();
        if ($dictionary->isObjectNew()) {
            return null;
        }

        return $dictionary;
    }
}
