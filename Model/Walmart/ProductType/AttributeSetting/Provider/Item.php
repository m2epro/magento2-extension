<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\ProductType\AttributeSetting\Provider;

class Item
{
    private string $name;
    /** @var string[] */
    private array $values;

    /**
     * @param string[] $values
     */
    public function __construct(string $name, array $values)
    {
        $this->name = $name;
        $this->values = $values;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
