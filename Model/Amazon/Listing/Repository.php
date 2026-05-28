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

    public function isSellingPolicyUseOnlyForUsMarketplaces(int $sellingPolicyId): bool
    {
        $collection = $this->listingCollectionFactory->createWithAmazonChildMode();
        $collection->addFieldToFilter(
            'template_selling_format_id',
            ['eq' => $sellingPolicyId]
        );
        $collection->addFieldToFilter(
            'marketplace_id',
            ['neq' => \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_US]
        );

        return $collection->getSize() === 0;
    }
}
