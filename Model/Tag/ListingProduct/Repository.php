<?php

namespace Ess\M2ePro\Model\Tag\ListingProduct;

class Repository
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory */
    private $collectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\ProductFactory */
    private $resourceProductFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\TagFactory */
    private $tagFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Model\ResourceModel\TagFactory $tagFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\ProductFactory $resourceProductFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resourceProductFactory = $resourceProductFactory;
        $this->tagFactory = $tagFactory;
    }

    /**
     * @param int[] $ids
     *
     * @return \Ess\M2ePro\Model\Tag\ListingProduct\Relation[]
     */
    public function findRelationsByProductIds(array $ids): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation::LISTING_PRODUCT_ID_FIELD,
            [
                'in' => array_unique($ids),
            ]
        );

        $result = [];
        /** @var \Ess\M2ePro\Model\Tag\ListingProduct\Relation $item */
        foreach ($collection as $item) {
            $result[] = $item;
        }

        return $result;
    }

    public function getCountOfErrorTagsForPeriod(string $componentMode, \DateTime $from, \DateTime $to): int
    {
        $tagTable = $this->tagFactory->create()->getMainTable();
        $productTable = $this->resourceProductFactory->create()->getMainTable();

        $select = $this->collectionFactory->create()->getSelect();
        $select->join(
            ['lp' => $productTable],
            'lp.id=listing_product_id',
            ['component_mode' => 'component_mode']
        );
        $select->join(
            ['tag' => $tagTable],
            'tag.id=tag_id'
        );

        $select->reset('columns');
        $select->columns('COUNT(*) AS value');

        $select->where(sprintf("lp.component_mode = '%s'", $componentMode));
        $select->where(sprintf("tag.error_code = '%s'", \Ess\M2ePro\Model\Tag::HAS_ERROR_ERROR_CODE));
        $select->where(
            sprintf(
                "main_table.create_date BETWEEN '%s' AND '%s'",
                $from->format('Y-m-d H:i:s'),
                $to->format('Y-m-d H:i:s')
            )
        );

        $count = $select->query()->fetch()['value'];

        return (int)$count;
    }

    public function getTotalCountOfErrorTags(string $componentMode): int
    {
        $tagTable = $this->tagFactory->create()->getMainTable();
        $productTable = $this->resourceProductFactory->create()->getMainTable();

        $select = $this->collectionFactory->create()->getSelect();
        $select->join(
            ['lp' => $productTable],
            'lp.id=listing_product_id',
            ['component_mode' => 'component_mode']
        );
        $select->join(
            ['tag' => $tagTable],
            'tag.id=tag_id'
        );

        $select->reset('columns');
        $select->columns('COUNT(*) AS value');

        $select->where(sprintf("lp.component_mode = '%s'", $componentMode));
        $select->where(sprintf("tag.error_code = '%s'", \Ess\M2ePro\Model\Tag::HAS_ERROR_ERROR_CODE));

        $count = $select->query()->fetch()['value'];

        return (int)$count;
    }
}
