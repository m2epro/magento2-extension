<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Tag\ListingProduct;

use Ess\M2ePro\Model\ResourceModel;

class Buffer
{
    private const MAX_PACK_SIZE = 500;

    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\CollectionFactory */
    private $tagCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory */
    private $tagListingProductRelationCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation */
    private $relationResource;

    /** @var array<string,\Ess\M2ePro\Model\Tag> */
    private $tags;
    /** @var array<int, string[]> */
    private $addNicks = [];
    /** @var array<int, string[]> */
    private $removeNicks = [];

    /**
     * @param ResourceModel\Tag\CollectionFactory $tagCollectionFactory
     * @param ResourceModel\Tag\ListingProduct\Relation\CollectionFactory $tagListingProductRelationCollectionFactory
     * @param ResourceModel\Tag\ListingProduct\Relation $relationResource
     */
    public function __construct(
        ResourceModel\Tag\CollectionFactory $tagCollectionFactory,
        ResourceModel\Tag\ListingProduct\Relation\CollectionFactory $tagListingProductRelationCollectionFactory,
        ResourceModel\Tag\ListingProduct\Relation $relationResource
    ) {
        $this->tagCollectionFactory = $tagCollectionFactory;
        $this->tagListingProductRelationCollectionFactory = $tagListingProductRelationCollectionFactory;
        $this->relationResource = $relationResource;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param string $tagNick
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addTag(\Ess\M2ePro\Model\Listing\Product $listingProduct, string $tagNick): void
    {
        $this->addTags($listingProduct, [$tagNick]);
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param string[] $tagNicks
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addTags(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $tagNicks): void
    {
        $existTags = $this->getExistsTagsCollectedByNick();
        foreach ($tagNicks as $tagNick) {
            if (!isset($existTags[$tagNick])) {
                throw new \Ess\M2ePro\Model\Exception\Logic(sprintf('Tag nick %s not found.', $tagNick));
            }

            $listingProductId = (int)$listingProduct->getId();

            $this->addNicks[$listingProductId][$tagNick] = $tagNick;
            if (isset($this->removeNicks[$listingProductId][$tagNick])) {
                unset($this->removeNicks[$listingProductId][$tagNick]);
            }
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param string $tagNick
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function removeTag(\Ess\M2ePro\Model\Listing\Product $listingProduct, string $tagNick): void
    {
        $this->removeTags($listingProduct, [$tagNick]);
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param string[] $tagNicks
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function removeTags(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $tagNicks): void
    {
        $existTags = $this->getExistsTagsCollectedByNick();
        foreach ($tagNicks as $tagNick) {
            if (!isset($existTags[$tagNick])) {
                throw new \Ess\M2ePro\Model\Exception\Logic(sprintf('Tag nick %s not found.', $tagNick));
            }

            $listingProductId = (int)$listingProduct->getId();

            $this->removeNicks[$listingProductId][$tagNick] = $tagNick;
            if (isset($this->addNicks[$listingProductId][$tagNick])) {
                unset($this->addNicks[$listingProductId][$tagNick]);
            }
        }
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function flush(): void
    {
        if (empty($this->addNicks) && empty($this->removeNicks)) {
            return;
        }

        $existRelations = $this->getExistsRelationsByProductId(
            array_merge(
                array_keys($this->addNicks),
                array_keys($this->removeNicks)
            )
        );
        $this->flushAdd($existRelations);
        $this->flushRemove($existRelations);

        $this->addNicks = [];
        $this->removeNicks = [];
    }

    /**
     * @param array $existsRelations
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function flushAdd(array $existsRelations): void
    {
        $pack = [];

        $existTags = $this->getExistsTagsCollectedByNick();
        foreach ($this->addNicks as $listingProductId => $addedTagNicks) {
            $existRelation = $existsRelations[$listingProductId] ?? [];
            foreach ($addedTagNicks as $addedTagNick) {
                if (!isset($existRelation[$addedTagNick])) {
                    $pack[$listingProductId][] = (int)$existTags[$addedTagNick]->getId();
                }
            }
        }

        if (!empty($pack)) {
            foreach (array_chunk($pack, self::MAX_PACK_SIZE, true) as $chunk) {
                $this->relationResource->insertTags($chunk);
            }
        }
    }

    /**
     * @param array $existsRelations
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function flushRemove(array $existsRelations): void
    {
        $pack = [];

        $existTags = $this->getExistsTagsCollectedByNick();
        foreach ($this->removeNicks as $listingProductId => $deletedTagNicks) {
            $existRelation = $existsRelations[$listingProductId] ?? [];
            foreach ($deletedTagNicks as $deletedTagNick) {
                if (isset($existRelation[$deletedTagNick])) {
                    $pack[$listingProductId][] = (int)$existTags[$deletedTagNick]->getId();
                }
            }
        }

        if (!empty($pack)) {
            foreach (array_chunk($pack, self::MAX_PACK_SIZE, true) as $chunk) {
                $this->relationResource->removeTags($chunk);
            }
        }
    }

    /**
     * @return \Ess\M2ePro\Model\Tag[]
     */
    private function getExistsTags(): array
    {
        if (isset($this->tags)) {
            return $this->tags;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Tag\Collection $collection */
        $collection = $this->tagCollectionFactory->create();
        $this->tags = $collection->getAll();

        return $this->tags;
    }

    /**
     * @return array<string, \Ess\M2ePro\Model\Tag>
     */
    private function getExistsTagsCollectedByNick(): array
    {
        $result = [];
        foreach ($this->getExistsTags() as $tag) {
            $result[$tag->getNick()] = $tag;
        }

        return $result;
    }

    /**
     * @param array $productsIds
     *
     * @return array<int, <string, \Ess\M2ePro\Model\Tag>>
     */
    private function getExistsRelationsByProductId(array $productsIds): array
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\Collection $collection */
        $collection = $this->tagListingProductRelationCollectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation::LISTING_PRODUCT_ID_FIELD,
            [
                'in' => array_unique($productsIds),
            ]
        );

        $tagsById = [];
        foreach ($this->getExistsTags() as $tag) {
            $tagsById[$tag->getId()] = $tag;
        }

        $result = [];
        /** @var \Ess\M2ePro\Model\Tag\ListingProduct\Relation $item */
        foreach ($collection as $item) {
            if (!isset($tagsById[$item->getTagId()])) {
                continue;
            }

            $tag = $tagsById[$item->getTagId()];
            $result[$item->getListingProductId()][$tag->getNick()] = $tag;
        }

        return $result;
    }
}
