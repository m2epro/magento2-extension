<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Dictionary\Category;

use Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category as ResourceModel;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category $categoryDictionaryResource;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category\CollectionFactory $collectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category $categoryDictionaryResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category\CollectionFactory $collectionFactory
    ) {
        $this->categoryDictionaryResource = $categoryDictionaryResource;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param \Ess\M2ePro\Model\Walmart\Dictionary\Category[] $categories
     */
    public function bulkCreate(array $categories): void
    {
        $insertData = [];
        foreach ($categories as $category) {
            $parentCategoryId = $category->isExistsParentCategoryId() ? $category->getParentCategoryId() : null;
            $productTypeNick = $category->isLeaf() ? $category->getProductTypeNick() : null;
            $productTypeTitle = $category->isLeaf() ? $category->getProductTypeTitle() : null;

            $insertData[] = [
                ResourceModel::COLUMN_MARKETPLACE_ID => $category->getMarketplaceId(),
                ResourceModel::COLUMN_CATEGORY_ID => $category->getCategoryId(),
                ResourceModel::COLUMN_PARENT_CATEGORY_ID => $parentCategoryId,
                ResourceModel::COLUMN_TITLE => $category->getTitle(),
                ResourceModel::COLUMN_IS_LEAF => $category->isLeaf(),
                ResourceModel::COLUMN_PRODUCT_TYPE_NICK => $productTypeNick,
                ResourceModel::COLUMN_PRODUCT_TYPE_TITLE => $productTypeTitle,
            ];
        }

        foreach (array_chunk($insertData, 1000) as $chunk) {
            $this->categoryDictionaryResource
                ->getConnection()
                ->insertMultiple(
                    $this->categoryDictionaryResource->getMainTable(),
                    $chunk
                );
        }
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Dictionary\Category[]
     */
    public function findRoots(int $marketplaceId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ResourceModel::COLUMN_MARKETPLACE_ID, $marketplaceId);
        $collection->addFieldToFilter(ResourceModel::COLUMN_PARENT_CATEGORY_ID, ['null' => true]);

        return array_values($collection->getItems());
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Dictionary\Category[]
     */
    public function findChildren(int $parentCategoryId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(ResourceModel::COLUMN_PARENT_CATEGORY_ID, $parentCategoryId);

        return array_values($collection->getItems());
    }

    public function removeByMarketplace(int $marketplaceId): void
    {
        $this->categoryDictionaryResource
            ->getConnection()
            ->delete(
                $this->categoryDictionaryResource->getMainTable(),
                [ResourceModel::COLUMN_MARKETPLACE_ID . ' = ?' => $marketplaceId]
            );
    }
}
