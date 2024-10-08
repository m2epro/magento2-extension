<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetCategories\Response;

class Category
{
    private int $id;
    private ?int $parentId;
    private string $title;
    private bool $isLeaf;
    private ?Category\ProductType $productType;

    public function __construct(
        int $id,
        ?int $parentId,
        string $title,
        bool $isLeaf
    ) {
        $this->id = $id;
        $this->parentId = $parentId;
        $this->title = $title;
        $this->isLeaf = $isLeaf;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function isLeaf(): bool
    {
        return $this->isLeaf;
    }

    public function getProductType(): ?Category\ProductType
    {
        return $this->productType;
    }

    public function setProductType(Category\ProductType $productType): void
    {
        $this->productType = $productType;
    }
}
