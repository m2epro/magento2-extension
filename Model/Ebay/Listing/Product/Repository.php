<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\CollectionFactory $ebayListingProductCollectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Item $ebayItemResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\CollectionFactory $ebayListingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Item $ebayItemResource
    ) {
        $this->ebayListingProductCollectionFactory = $ebayListingProductCollectionFactory;
        $this->ebayItemResource = $ebayItemResource;
    }

    public function findByItemId(string $itemId): ?\Ess\M2ePro\Model\Ebay\Listing\Product
    {
        $collection = $this->ebayListingProductCollectionFactory->create();

        $collection->join(
            ['elp' => $this->ebayItemResource->getMainTable()],
            sprintf(
                'main_table.%s = elp.%s',
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::COLUMN_EBAY_ITEM_ID,
                \Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ID
            ),
            []
        );

        $collection->addFieldToFilter(\Ess\M2ePro\Model\ResourceModel\Ebay\Item::COLUMN_ITEM_ID, $itemId);

        $item = $collection->getFirstItem();

        if ($item->isObjectNew()) {
            return null;
        }

        return $item;
    }
}
