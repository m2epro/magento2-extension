<?php

namespace Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria;

class Response
{
    /** @var array */
    private $categories = [];

    public function addCategory(string $name, bool $isLeaf, array $nicksOfProductTypes): void
    {
        $this->categories[] = [
            'name' => $name,
            'isLeaf' => $isLeaf,
            'nicksOfProductTypes' => $nicksOfProductTypes,
        ];
    }

    /**
     * @return list<array{name: string, isLeaf: bool, nicksOfProductTypes: string[]}>
     */
    public function getCategories(): array
    {
        return $this->categories;
    }
}
