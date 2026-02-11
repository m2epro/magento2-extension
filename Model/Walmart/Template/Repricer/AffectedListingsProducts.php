<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Template\Repricer;

class AffectedListingsProducts extends \Ess\M2ePro\Model\Template\AffectedListingsProductsAbstract
{
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Listing $walmartListingResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Listing $walmartListingResource,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory, $data);
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->walmartListingResource = $walmartListingResource;
    }

    public function loadCollection(array $filters = [])
    {
        $collection = $this->listingProductCollectionFactory->createWithWalmartChildMode();

        $collection->join(
            ['walmart_listing' => $this->walmartListingResource->getMainTable()],
            'walmart_listing.listing_id = main_table.listing_id',
            [
                \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_TEMPLATE_REPRICER_ID,
            ]
        );
        $collection->addFieldToFilter([
            sprintf(
                'second_table.%s',
                \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_TEMPLATE_REPRICER_ID
            ),
            sprintf(
                'walmart_listing.%s',
                \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_TEMPLATE_REPRICER_ID
            ),
        ], [
            ['eq' => (int)$this->model->getId()],
            ['eq' => (int)$this->model->getId()],
        ]);

        return $collection;
    }
}
