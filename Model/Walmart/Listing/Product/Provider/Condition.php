<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Provider;

class Condition
{
    public ?string $value;
    public bool $isNotFoundMagentoAttribute = false;

    public static function createWithValue(string $value): self
    {
        return new self($value);
    }

    public static function createWithoutMagentoAttribute(): self
    {
        $condition = new self();
        $condition->isNotFoundMagentoAttribute = true;

        return $condition;
    }

    private function __construct(?string $value = null)
    {
        $this->value = $value;
    }
}
