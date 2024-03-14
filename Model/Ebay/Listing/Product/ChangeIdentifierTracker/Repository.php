<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\ChangeIdentifierTracker;

class Repository
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Synchronization\CollectionFactory */
    private $syncTemplateCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\CollectionFactory */
    private $ebayListingCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\CollectionFactory */
    private $ebayListingProductCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Synchronization\CollectionFactory $syncTemplateCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\CollectionFactory $ebayListingCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\CollectionFactory $ebayListingProductCollectionFactory
    ) {
        $this->syncTemplateCollectionFactory = $syncTemplateCollectionFactory;
        $this->ebayListingCollectionFactory = $ebayListingCollectionFactory;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->ebayListingProductCollectionFactory = $ebayListingProductCollectionFactory;
    }

    /**
     * @return int[]
     * @throws \Exception
     */
    public function getIdsOfSyncTemplatesWithEnabledReviseProductIds(): array
    {
        $collection = $this->syncTemplateCollectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Synchronization::COLUMN_REVISE_UPDATE_PRODUCT_IDENTIFIERS,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization::REVISE_UPDATE_PRODUCT_IDENTIFIERS_ENABLED
        );

        $collection->addFieldToSelect(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Synchronization::COLUMN_TEMPLATE_SYNCHRONIZATION_ID
        );

        return array_map(function (\Ess\M2ePro\Model\Ebay\Template\Synchronization $template) {
            return $template->getTemplateSynchronizationId();
        }, $collection->getItems());
    }

    /**
     * @param int[] $syncTemplateIds
     * @throws \Exception
     */
    public function getIdsOfListingsBySyncTemplateIds(array $syncTemplateIds): array
    {
        $collection = $this->ebayListingCollectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing::COLUMN_TEMPLATE_SYNCHRONIZATION_ID,
            ['in' => $syncTemplateIds]
        );

        return array_map(function (\Ess\M2ePro\Model\Ebay\Listing $listing) {
            return $listing->getListingId();
        }, $collection->getItems());
    }

    /**
     * Get IDs of listing products that have status "Listed" and don't have a personal synchronization template.
     * @return int[]
     */
    public function getIDsOfListedListingProductsByListingIds(array $listingsIds): array
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->join(
            ['elp' => $this->ebayListingProductCollectionFactory->create()->getMainTable()],
            sprintf(
                'main_table.%s = elp.%s',
                \Ess\M2ePro\Model\ResourceModel\Listing\Product::COLUMN_ID,
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_LISTING_PRODUCT_ID
            )
        );

        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing\Product::LISTING_ID_FIELD,
            ['in' => $listingsIds]
        );
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing\Product::STATUS_FIELD,
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED
        );
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_TEMPLATE_SYNCHRONIZATION_ID,
            ['null' => true]
        );

        $collection->addFieldToSelect(
            \Ess\M2ePro\Model\ResourceModel\Listing\Product::COLUMN_ID
        );

        return array_map(function (\Ess\M2ePro\Model\Listing\Product $listingProduct) {
            return (int)$listingProduct->getId();
        }, $collection->getItems());
    }

    /**
     * Get IDs of listing products that have status "Listed" and a personal synchronization template.
     * @return int[]
     */
    public function getIDsOfListedListingProductsBySyncTemplateIds(array $syncTemplateIds): array
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->join(
            ['elp' => $this->ebayListingProductCollectionFactory->create()->getMainTable()],
            sprintf(
                'main_table.%s = elp.%s',
                \Ess\M2ePro\Model\ResourceModel\Listing\Product::COLUMN_ID,
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_LISTING_PRODUCT_ID
            )
        );

        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing\Product::STATUS_FIELD,
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED
        );
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_TEMPLATE_SYNCHRONIZATION_ID,
            ['in' => $syncTemplateIds]
        );

        $collection->addFieldToSelect(
            \Ess\M2ePro\Model\ResourceModel\Listing\Product::COLUMN_ID
        );

        return array_map(function (\Ess\M2ePro\Model\Listing\Product $listingProduct) {
            return (int)$listingProduct->getId();
        }, $collection->getItems());
    }
}
