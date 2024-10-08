<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\ProductType\AttributeSetting;

class Value
{
    private const TYPE_PRODUCT_ATTRIBUTE_CODE = 'product_attribute_code';
    private const TYPE_CUSTOM = 'custom';

    private string $type;
    private string $value;

    public static function createAsProductAttributeCode(string $value): self
    {
        return new self(self::TYPE_PRODUCT_ATTRIBUTE_CODE, $value);
    }

    public static function createAsCustom(string $value): self
    {
        return new self(self::TYPE_CUSTOM, $value);
    }

    private function __construct(string $type, string $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function isProductAttributeCode(): bool
    {
        return $this->type === self::TYPE_PRODUCT_ATTRIBUTE_CODE;
    }

    public function isCustom(): bool
    {
        return $this->type === self::TYPE_CUSTOM;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
