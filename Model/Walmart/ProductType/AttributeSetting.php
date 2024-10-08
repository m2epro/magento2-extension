<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\ProductType;

class AttributeSetting
{
    private string $attributeName;
    private array $values = [];

    public function __construct(string $attributeName)
    {
        $this->attributeName = $attributeName;
    }

    public function addValue(AttributeSetting\Value $value): void
    {
        $this->values[] = $value;
    }

    public function getAttributeName(): string
    {
        return $this->attributeName;
    }

    /**
     * @return AttributeSetting\Value[]
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
