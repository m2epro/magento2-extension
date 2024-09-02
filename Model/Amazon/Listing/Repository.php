<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\CollectionFactory $listingCollectionFactory
    ) {
        $this->listingCollectionFactory = $listingCollectionFactory;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing[]
     */
    public function getAll(): array
    {
        $listingsCollection = $this->listingCollectionFactory->createWithAmazonChildMode();

        return array_values($listingsCollection->getItems());
    }
}
