<?php

namespace Ess\M2ePro\Model\Tag;

class Repository
{
    /** @var \Ess\M2ePro\Model\TagFactory */
    private $tagFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\CollectionFactory */
    private $collectionFactory;
    /** @var \Ess\M2ePro\Model\Tag\EntityFactory */
    private $entityFactory;

    private $isLoad = false;
    private $entitiesByErrorCode = [];
    private $entitiesById = [];
    /** @var \Ess\M2ePro\Model\Tag[] */
    private $tags = [];

    public function __construct(
        \Ess\M2ePro\Model\TagFactory $tagFactory,
        \Ess\M2ePro\Model\ResourceModel\Tag\CollectionFactory $collectionFactory,
        EntityFactory $entityFactory
    ) {
        $this->tagFactory = $tagFactory;
        $this->entityFactory = $entityFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function create(\Ess\M2ePro\Model\Tag $tag): void
    {
        if ($this->has($tag)) {
            return;
        }

        $entity = $this->entityFactory->create(
            $tag->getText(),
            $tag->getErrorCode(),
            \Ess\M2ePro\Helper\Date::createCurrentGmt()
        );
        $entity->save();

        $this->tags[$entity->getErrorCode()] = $tag;
        $this->entitiesById[$entity->getId()] = $entity;
        $this->entitiesByErrorCode[$entity->getErrorCode()] = $entity;
    }

    public function has(\Ess\M2ePro\Model\Tag $tag): bool
    {
        $this->load();

        return isset($this->tags[$tag->getErrorCode()]);
    }

    /**
     * @return \Ess\M2ePro\Model\Tag[]
     */
    public function getAllTags(): array
    {
        $this->load();

        return array_values($this->tags);
    }

    public function findEntityById(int $id): ?\Ess\M2ePro\Model\Tag\Entity
    {
        $this->load();

        return $this->entitiesById[$id] ?? null;
    }

    /**
     * @return \Ess\M2ePro\Model\Tag\Entity[]
     */
    public function getAllEntities(): array
    {
        $this->load();

        return array_values($this->entitiesById);
    }

    // ----------------------------------------

    private function load(): void
    {
        if ($this->isLoad) {
            return;
        }

        $this->entitiesById = [];
        $this->entitiesByErrorCode = [];

        $collection = $this->collectionFactory->create();
        foreach ($collection->getAll() as $item) {
            $this->entitiesById[$item->getId()] = $item;
            $this->entitiesByErrorCode[$item->getErrorCode()] = $item;
            $this->tags[$item->getErrorCode()] = $this->tagFactory->create($item->getErrorCode(), $item->getText());
        }

        $this->isLoad = true;
    }
}
