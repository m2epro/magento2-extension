<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Tag\ListingProduct;

class Repository
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory */
    private $collectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
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
}
