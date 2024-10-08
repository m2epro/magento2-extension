<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Dictionary;

use Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category as ResourceModel;

class Category extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\Category::class);
    }

    public function init(
        int $marketplaceId,
        int $categoryId,
        string $title
    ): self {
        $this->setData(ResourceModel::COLUMN_MARKETPLACE_ID, $marketplaceId);
        $this->setData(ResourceModel::COLUMN_CATEGORY_ID, $categoryId);
        $this->setData(ResourceModel::COLUMN_TITLE, $title);
        $this->setIsLeaf(false);

        return $this;
    }

    public function getMarketplaceId(): int
    {
        return (int)$this->getDataByKey(ResourceModel::COLUMN_MARKETPLACE_ID);
    }

    public function getCategoryId(): int
    {
        return (int)$this->getDataByKey(ResourceModel::COLUMN_CATEGORY_ID);
    }

    public function getTitle(): string
    {
        return $this->getDataByKey(ResourceModel::COLUMN_TITLE);
    }

    public function isExistsParentCategoryId(): bool
    {
        return $this->getDataByKey(ResourceModel::COLUMN_PARENT_CATEGORY_ID) !== null;
    }

    public function getParentCategoryId(): int
    {
        if (!$this->isExistsParentCategoryId()) {
            throw new \LogicException('Parent category id not set');
        }

        return (int)$this->getDataByKey(ResourceModel::COLUMN_PARENT_CATEGORY_ID);
    }

    public function setParentCategoryId(int $parentCategoryId): self
    {
        $this->setData(ResourceModel::COLUMN_PARENT_CATEGORY_ID, $parentCategoryId);

        return $this;
    }

    public function isLeaf(): bool
    {
        return (bool)$this->getDataByKey(ResourceModel::COLUMN_IS_LEAF);
    }

    public function getProductTypeNick(): string
    {
        if (!$this->isLeaf()) {
            throw new \LogicException('Category is not leaf');
        }

        return $this->getDataByKey(ResourceModel::COLUMN_PRODUCT_TYPE_NICK);
    }

    public function getProductTypeTitle(): string
    {
        if (!$this->isLeaf()) {
            throw new \LogicException('Category is not leaf');
        }

        return $this->getDataByKey(ResourceModel::COLUMN_PRODUCT_TYPE_TITLE);
    }

    public function markAsLeaf(
        string $productTypeNick,
        string $productTypeTitle
    ): self {
        $this->setIsLeaf(true);
        $this->setData(ResourceModel::COLUMN_PRODUCT_TYPE_NICK, $productTypeNick);
        $this->setData(ResourceModel::COLUMN_PRODUCT_TYPE_TITLE, $productTypeTitle);

        return $this;
    }

    private function setIsLeaf(bool $isLeaf): void
    {
        $this->setData(ResourceModel::COLUMN_IS_LEAF, $isLeaf);
    }
}
