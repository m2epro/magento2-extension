<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Template\Repricer;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer\CollectionFactory $collectionFactory;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer $resource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer $resource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
    }

    public function find(int $id): ?\Ess\M2ePro\Model\Walmart\Template\Repricer
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_ID,
            ['eq' => $id]
        );

        $template = $collection->getFirstItem();
        if ($template->isObjectNew()) {
            return null;
        }

        return $template;
    }

    public function get(int $id): \Ess\M2ePro\Model\Walmart\Template\Repricer
    {
        $template = $this->find($id);
        if ($template === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic("Repricer Policy by id '$id' not found.");
        }

        return $template;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Repricer[]
     */
    public function getAllSortedByTitle(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->setOrder(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::COLUMN_TITLE,
            \Magento\Framework\Data\Collection::SORT_ORDER_ASC
        );

        return array_values($collection->getItems());
    }

    public function delete(\Ess\M2ePro\Model\Walmart\Template\Repricer $template): void
    {
        $this->resource->delete($template);
    }
}
