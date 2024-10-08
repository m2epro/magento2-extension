<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Dictionary;

class CategoryFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createAsRoot(
        int $marketplaceId,
        int $categoryId,
        string $title
    ): Category {
        return $this->create(
            $marketplaceId,
            $categoryId,
            $title
        );
    }

    public function createAsChild(
        int $marketplaceId,
        int $parentCategoryId,
        int $categoryId,
        string $title
    ): Category {
        $category = $this->create(
            $marketplaceId,
            $categoryId,
            $title
        );
        $category->setParentCategoryId($parentCategoryId);

        return $category;
    }

    public function createAsLeaf(
        int $marketplaceId,
        int $parentCategoryId,
        int $categoryId,
        string $title,
        string $productTypeNick,
        string $productTypeTitle
    ): Category {
        $category = $this->create(
            $marketplaceId,
            $categoryId,
            $title
        );
        $category->setParentCategoryId($parentCategoryId);
        $category->markAsLeaf(
            $productTypeNick,
            $productTypeTitle
        );

        return $category;
    }

    private function create(
        int $marketplaceId,
        int $categoryId,
        string $title
    ): Category {
        return $this->createEmpty()
                    ->init(
                        $marketplaceId,
                        $categoryId,
                        $title
                    );
    }

    public function createEmpty(): Category
    {
        return $this->objectManager->create(Category::class);
    }
}
