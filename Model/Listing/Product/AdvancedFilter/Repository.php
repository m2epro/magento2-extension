<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Listing\Product\AdvancedFilter;

use Ess\M2ePro\Model\ResourceModel\Listing\Product\AdvancedFilter as ResourceModel;

class Repository
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\AdvancedFilter\CollectionFactory */
    private $collectionFactory;
    /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter */
    private $advancedFilter;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\AdvancedFilter\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter $advancedFilter
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->advancedFilter = $advancedFilter;
    }

    public function getAdvancedFilter(int $id): \Ess\M2ePro\Model\Listing\Product\AdvancedFilter
    {
        $collection = $this->collectionFactory->create();
        $collection->addFilter(ResourceModel::COLUMN_ID, $id);
        $collection->getSelect()->limit(1);

        /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter $advancedFilter */
        $advancedFilter = $collection->getFirstItem();
        if ($advancedFilter->isEmpty()) {
            throw new \LogicException(sprintf('Not found entity by id - [%d]', $id));
        }

        return $advancedFilter;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\AdvancedFilter[]
     */
    public function findItemsByModelNick(string $modelNick): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFilter(ResourceModel::COLUMN_MODEL_NICK, $modelNick);

        return array_values($collection->getItems());
    }

    public function isExistItemsWithModelNick(string $modelNick): bool
    {
        $collection = $this->collectionFactory->create();
        $collection->addFilter(ResourceModel::COLUMN_MODEL_NICK, $modelNick);

        return (bool)$collection->getSize();
    }

    public function update(
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter $advancedFilter,
        string $title,
        string $conditions,
        \DateTime $updateDate
    ): void {
        $advancedFilter->setTitle($title);
        $advancedFilter->setConditionals($conditions);
        $advancedFilter->setUpdateDate($updateDate);

        $advancedFilter->save();
    }

    public function save(
        string $modelNick,
        string $title,
        string $conditionals,
        \DateTime $createDate
    ): \Ess\M2ePro\Model\Listing\Product\AdvancedFilter {
        $this->advancedFilter->setModelNick($modelNick);
        $this->advancedFilter->setTitle($title);
        $this->advancedFilter->setConditionals($conditionals);
        $this->advancedFilter->setCreateDate($createDate);
        $this->advancedFilter->setUpdateDate($createDate);

        $this->advancedFilter->save();

        return $this->advancedFilter;
    }

    public function remove(\Ess\M2ePro\Model\Listing\Product\AdvancedFilter $advancedFilter): void
    {
        $advancedFilter->delete();
    }
}
