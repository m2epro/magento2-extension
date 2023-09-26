<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\CategoryFinder;

class Category
{
    /** @var string */
    private $name;
    /** @var bool */
    private $isLeaf;
    /** @var ProductType[] */
    private $productTypes = [];
    /** @var string[] */
    private $path;

    public function __construct(string $name, bool $isLeaf)
    {
        $this->name = $name;
        $this->isLeaf = $isLeaf;
    }

    public function setPath(array $path): void
    {
        $this->path = $path;
    }

    public function addProductType(ProductType $productType): void
    {
        $this->productTypes[] = $productType;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIsLeaf(): bool
    {
        return $this->isLeaf;
    }

    public function getProductTypes(): array
    {
        return $this->productTypes;
    }

    public function getPath(): ?array
    {
        return $this->path;
    }
}
