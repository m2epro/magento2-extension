<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Bundle\Options\Mapping\CollectionFactory $collectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Ebay\Bundle\Options\Mapping $resource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Bundle\Options\Mapping\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Bundle\Options\Mapping $resource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping[]
     */
    public function getAll(): array
    {
        $collection = $this->collectionFactory->create();

        return array_values($collection->getItems());
    }

    public function findByTitle(string $title): ?\Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Ebay\Bundle\Options\Mapping::COLUMN_OPTION_TITLE,
            ['eq' => $title]
        );

        $mapping = $collection->getFirstItem();

        if ($mapping->isObjectNew()) {
            return null;
        }

        return $mapping;
    }

    public function create(\Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping $mapping)
    {
        $this->resource->save($mapping);
    }

    public function save(\Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping $mapping)
    {
        $this->resource->save($mapping);
    }

    public function delete(\Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping $mapping)
    {
        $this->resource->delete($mapping);
    }
}
