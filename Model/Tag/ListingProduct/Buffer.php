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

    /** @var array<int, Buffer\Item> */
    private array $items = [];
    private ResourceModel\Tag\ListingProduct\Relation $relationResource;
    private \Ess\M2ePro\Model\Tag\Repository $tagRepository;
    private Repository $listingProductTagRepository;
    private ResourceModel\Listing\Product $listingProductResource;
    private \Ess\M2ePro\Model\Tag\BlockingErrors $blockingErrors;

    public function __construct(
        \Ess\M2ePro\Model\Tag\Repository $tagRepository,
        ResourceModel\Tag\ListingProduct\Relation $relationResource,
        \Ess\M2ePro\Model\Tag\ListingProduct\Repository $listingProductTagRepository,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\Tag\BlockingErrors $blockingErrors
    ) {
        $this->relationResource = $relationResource;
        $this->tagRepository = $tagRepository;
        $this->listingProductTagRepository = $listingProductTagRepository;
        $this->listingProductResource = $listingProductResource;
        $this->blockingErrors = $blockingErrors;
    }

    public function addTag(\Ess\M2ePro\Model\Listing\Product $listingProduct, \Ess\M2ePro\Model\Tag $tag): void
    {
        $this->addTags($listingProduct, [$tag]);
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product $listingProduct
     * @param \Ess\M2ePro\Model\Tag[] $tags
     *
     * @return void
     */
    public function addTags(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $tags): void
    {
        $item = $this->getItem((int)$listingProduct->getId());
        foreach ($tags as $tag) {
            $item->addTag($tag);
        }
    }

    public function removeAllTags(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $item = $this->getItem((int)$listingProduct->getId());
        foreach ($this->tagRepository->getAllTags() as $tag) {
            $item->removeTag($tag);
        }
    }

    private function getItem(int $productId): Buffer\Item
    {
        return $this->items[$productId] ?? $this->items[$productId] = new Buffer\Item($productId);
    }

    // ----------------------------------------

    public function flush(): void
    {
        if (empty($this->items)) {
            return;
        }

        $this->createNewTags($this->items);

        $tagsEntitiesByErrorCode = $this->getTagsEntitiesByErrorCode();
        $existedRelations = $this->getExistsRelationsByProductId($this->items);

        $this->flushAdd($this->items, $tagsEntitiesByErrorCode, $existedRelations);
        $this->flushRemove($this->items, $tagsEntitiesByErrorCode, $existedRelations);

        $this->items = [];
    }

    /**
     * @param Buffer\Item[] $items
     *
     * @return void
     */
    private function createNewTags(array $items): void
    {
        foreach ($items as $item) {
            foreach ($item->getAddedTags() as $tag) {
                $this->tagRepository->create($tag);
            }
        }
    }

    /**
     * @return array<string, \Ess\M2ePro\Model\Tag\Entity>
     */
    private function getTagsEntitiesByErrorCode(): array
    {
        $result = [];
        foreach ($this->tagRepository->getAllEntities() as $entity) {
            $result[$entity->getErrorCode()] = $entity;
        }

        return $result;
    }

    /**
     * @param Buffer\Item[] $items
     *
     * @return array<int, <string, \Ess\M2ePro\Model\Tag\Entity>>
     */
    private function getExistsRelationsByProductId(array $items): array
    {
        $productsIds = array_map(
            function ($item) {
                return $item->getProductId();
            },
            $items
        );
        $relations = $this->listingProductTagRepository->findRelationsByProductIds($productsIds);

        $result = [];
        foreach ($relations as $relation) {
            $tagEntity = $this->tagRepository->findEntityById($relation->getTagId());
            if ($tagEntity === null) {
                continue;
            }

            $result[$relation->getListingProductId()][$tagEntity->getErrorCode()] = $tagEntity;
        }

        return $result;
    }

    /**
     * @param \Ess\M2ePro\Model\Tag\ListingProduct\Buffer\Item[] $items
     * @param array $tagsEntitiesByErrorCode
     * @param array $existsRelations
     *
     * @return void
     */
    private function flushAdd(array $items, array $tagsEntitiesByErrorCode, array $existsRelations): void
    {
        $pack = [];
        $blockingErrorsPack = [];

        $ebayBlockingErrorsList = $this->blockingErrors->getList();

        foreach ($items as $item) {
            $existRelation = $existsRelations[$item->getProductId()] ?? [];
            foreach ($item->getAddedTags() as $tag) {
                if (!isset($existRelation[$tag->getErrorCode()])) {
                    $pack[$item->getProductId()][] = (int)$tagsEntitiesByErrorCode[$tag->getErrorCode()]->getId();

                    if (in_array($tag->getErrorCode(), $ebayBlockingErrorsList, true)) {
                        $blockingErrorsPack[] = $item->getProductId();
                    }
                }
            }
        }

        if (!empty($pack)) {
            foreach (array_chunk($pack, self::MAX_PACK_SIZE, true) as $chunk) {
                $this->relationResource->insertTags($chunk);
            }
        }

        if (!empty($blockingErrorsPack)) {
            $lastBlockingErrorDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
            foreach (array_chunk($blockingErrorsPack, self::MAX_PACK_SIZE, true) as $chunk) {
                $this->listingProductResource->updateLastBlockingErrorDate($chunk, $lastBlockingErrorDate);
            }
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Tag\ListingProduct\Buffer\Item[] $items
     * @param array $tagsEntitiesByErrorCode
     * @param array $existsRelations
     *
     * @return void
     */
    private function flushRemove(array $items, array $tagsEntitiesByErrorCode, array $existsRelations): void
    {
        $pack = [];

        foreach ($items as $item) {
            $existRelation = $existsRelations[$item->getProductId()] ?? [];
            foreach ($item->getRemovedTags() as $tag) {
                if (isset($existRelation[$tag->getErrorCode()])) {
                    $pack[$item->getProductId()][] = (int)$tagsEntitiesByErrorCode[$tag->getErrorCode()]->getId();
                }
            }
        }

        if (!empty($pack)) {
            foreach (array_chunk($pack, self::MAX_PACK_SIZE, true) as $chunk) {
                $this->relationResource->removeTags($chunk);
            }
        }
    }
}
