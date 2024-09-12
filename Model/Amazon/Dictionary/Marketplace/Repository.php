<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Dictionary\Marketplace;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace $resource;
    private \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace\CollectionFactory $collectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace $resource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace\CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
    }

    public function findByMarketplace(
        \Ess\M2ePro\Model\Marketplace $marketplace
    ): ?\Ess\M2ePro\Model\Amazon\Dictionary\Marketplace {
        return $this->findByMarketplaceId((int)$marketplace->getId());
    }

    public function findByMarketplaceId(int $marketplaceId): ?\Ess\M2ePro\Model\Amazon\Dictionary\Marketplace
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace::COLUMN_MARKETPLACE_ID,
            ['eq' => $marketplaceId]
        );

        $dictionary = $collection->getFirstItem();
        if ($dictionary->isObjectNew()) {
            return null;
        }

        return $dictionary;
    }

    public function create(\Ess\M2ePro\Model\Amazon\Dictionary\Marketplace $dictionaryMarketplace): void
    {
        $this->resource->save($dictionaryMarketplace);
    }

    public function removeByMarketplace(\Ess\M2ePro\Model\Marketplace $marketplace): void
    {
        $this->resource
            ->getConnection()
            ->delete(
                $this->resource->getMainTable(),
                ['marketplace_id = ?' => $marketplace->getId()]
            );
    }
}
