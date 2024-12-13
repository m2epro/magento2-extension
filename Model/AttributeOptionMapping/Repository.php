<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AttributeOptionMapping;

use Ess\M2ePro\Model\ResourceModel\AttributeOptionMapping\Pair as PairResource;

class Repository
{
    private PairResource $resource;
    private \Ess\M2ePro\Model\ResourceModel\AttributeOptionMapping\Pair\CollectionFactory $collectionFactory;

    public function __construct(
        PairResource $resource,
        \Ess\M2ePro\Model\ResourceModel\AttributeOptionMapping\Pair\CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
    }

    public function create(Pair $pair): void
    {
        $pair->isObjectCreatingState(true);
        $this->resource->save($pair);
    }

    public function save(Pair $pair): void
    {
        $this->resource->save($pair);
    }

    public function remove(Pair $pair): void
    {
        $this->resource->delete($pair);
    }

    /**
     * @param string $component
     *
     * @return Pair[]
     */
    public function findByComponent(string $component): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(PairResource::COLUMN_COMPONENT, ['eq' => $component]);

        return array_values($collection->getItems());
    }

    /**
     * @param string $component
     * @param string $type
     *
     * @return Pair[]
     */
    public function findByComponentAndType(string $component, string $type): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(PairResource::COLUMN_COMPONENT, ['eq' => $component])
                   ->addFieldToFilter(PairResource::COLUMN_TYPE, ['eq' => $type]);

        return array_values($collection->getItems());
    }
}
