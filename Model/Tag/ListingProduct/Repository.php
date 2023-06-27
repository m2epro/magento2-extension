<?php

namespace Ess\M2ePro\Model\Tag\ListingProduct;

class Repository
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory */
    private $relationCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\CollectionFactory */
    private $tagCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation */
    private $relationResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory $relationCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Tag\CollectionFactory $tagCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation $relationResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource
    ) {
        $this->relationCollectionFactory = $relationCollectionFactory;
        $this->tagCollectionFactory = $tagCollectionFactory;
        $this->relationResource = $relationResource;
        $this->listingProductResource = $listingProductResource;
    }

    /**
     * @param int[] $ids
     *
     * @return \Ess\M2ePro\Model\Tag\ListingProduct\Relation[]
     */
    public function findRelationsByProductIds(array $ids): array
    {
        $collection = $this->relationCollectionFactory->create();
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

    /**
     * @param string $componentMode
     *
     * @return \Ess\M2ePro\Model\Tag\Entity[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTagEntitiesWithoutHasErrorsTag(string $componentMode): array
    {
        $collection = $this->tagCollectionFactory->create();

        $collection->getSelect()->join(
            ['rel' => $this->relationResource->getMainTable()],
            'main_table.id = rel.tag_id'
        );

        $collection->getSelect()->join(
            ['lp' => $this->listingProductResource->getMainTable()],
            'rel.listing_product_id = lp.id'
        );

        $collection->distinct(true);

        $collection->getSelect()->reset('columns');
        $collection->getSelect()->columns([
            'main_table.' . \Ess\M2ePro\Model\ResourceModel\Tag::ID_FIELD,
            'main_table.' . \Ess\M2ePro\Model\ResourceModel\Tag::TEXT_FIELD,
            'main_table.' . \Ess\M2ePro\Model\ResourceModel\Tag::ERROR_CODE_FIELD,
            'main_table.' . \Ess\M2ePro\Model\ResourceModel\Tag::CREATE_DATE_FIELD,
        ]);

        $collection->getSelect()->where('lp.component_mode = ?', $componentMode);
        $collection->getSelect()->where(
            \Ess\M2ePro\Model\ResourceModel\Tag::ERROR_CODE_FIELD . ' != ?',
            \Ess\M2ePro\Model\Tag::HAS_ERROR_ERROR_CODE
        );

        return $collection->getAll();
    }
}
