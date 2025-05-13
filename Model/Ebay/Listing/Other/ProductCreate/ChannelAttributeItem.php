<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate;

class ChannelAttributeItem
{
    private string $attributeCode;
    private string $attributeLabel;
    private array $attributeValues;

    public function __construct(string $attributeCode, string $attributeLabel, array $attributeValues)
    {
        $this->attributeCode = $attributeCode;
        $this->attributeLabel = $attributeLabel;
        $this->attributeValues = $attributeValues;
    }

    public function setAttributeCode(string $attributeCode): void
    {
        $this->attributeCode = $attributeCode;
    }

    public function getAttributeCode(): string
    {
        return $this->attributeCode;
    }

    public function setAttributeValues(array $attributeValues): void
    {
        $this->attributeValues = $attributeValues;
    }

    public function getAttributeValues(): array
    {
        return $this->attributeValues;
    }

    public function setAttributeLabel(string $attributeLabel): void
    {
        $this->attributeLabel = $attributeLabel;
    }

    public function getAttributeLabel(): string
    {
        return $this->attributeLabel;
    }
}
