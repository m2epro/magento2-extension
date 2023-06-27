<?php

namespace Ess\M2ePro\Model\Dashboard\ListingProductIssues;

class Repository
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory */
    private $relationCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag */
    private $tagResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory $relationCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Tag $tagResource
    ) {
        $this->relationCollectionFactory = $relationCollectionFactory;
        $this->listingProductResource = $listingProductResource;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->tagResource = $tagResource;
    }

    /**
     * @param string $componentMode
     * @param int $limit
     *
     * @return Repository\Record[]
     */
    public function getGroupedRecords(string $componentMode, int $limit): array
    {
        $totalCountOfListingProducts = $this->getTotalCountOfListingProducts($componentMode);

        $collection = $this->relationCollectionFactory->create();

        $collection->join(
            ['tag' => $this->tagResource->getMainTable()],
            'main_table.tag_id = tag.id'
        );

        $collection->join(
            ['lp' => $this->listingProductResource->getMainTable()],
            'main_table.listing_product_id = lp.id'
        );

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'total' => new \Magento\Framework\DB\Sql\Expression('COUNT(*)'),
            'tag_id' => 'tag.id',
            'tag_text' => 'tag.text',
        ]);
        $collection->getSelect()->where('tag.error_code != ?', \Ess\M2ePro\Model\Tag::HAS_ERROR_ERROR_CODE);
        $collection->getSelect()->where('lp.component_mode = ?', $componentMode);
        $collection->getSelect()->group('main_table.tag_id');
        $collection->getSelect()->order('total DESC');
        $collection->getSelect()->limit($limit);

        $queryData = $collection->getSelect()->query()->fetchAll();

        $records = [];
        foreach ($queryData as $item) {
            $total = (int)$item['total'];
            $impactRate = $total * 100 / $totalCountOfListingProducts;
            $records[] = new Repository\Record(
                $total,
                (int)$item['tag_id'],
                (float)$impactRate,
                $item['tag_text']
            );
        }

        return $records;
    }

    private function getTotalCountOfListingProducts(string $componentMode): int
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->whereComponentMode($componentMode);

        return $collection->getSize();
    }
}
