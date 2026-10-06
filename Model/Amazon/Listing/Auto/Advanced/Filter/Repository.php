<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter\CollectionFactory $collectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter\CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    public function get(int $id): \Ess\M2ePro\Model\Listing\Auto\Advanced\Filter
    {
        $advancedFilter = $this->find($id);
        if ($advancedFilter === null) {
            throw new \Ess\M2ePro\Model\Exception\EntityNotFound('Not found Advanced Filter with id ' . $id);
        }

        return $advancedFilter;
    }

    public function find(int $id): ?\Ess\M2ePro\Model\Listing\Auto\Advanced\Filter
    {
        $collection = $this->collectionFactory->createWithAmazonChildMode();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_ID,
            ['eq' => $id]
        );

        $instance = $collection->getFirstItem();
        if ($instance->isObjectNew()) {
            return null;
        }

        return $instance;
    }

    public function isUniqueTitle(string $title, int $listingId, ?int $id = null): bool
    {
        $collection = $this->collectionFactory->createWithAmazonChildMode();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_TITLE,
            ['eq' => $title]
        );
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_LISTING_ID,
            ['eq' => $listingId]
        );

        if ($id !== null) {
            $collection->addFieldToFilter(
                \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Advanced\Filter::COLUMN_ID,
                ['neq' => $id]
            );
        }

        return $collection->getSize() === 0;
    }
}
