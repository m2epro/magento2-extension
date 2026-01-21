<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\ItemInfoLoader;

class Specific
{
    public string $name;
    public string $source;
    public array $values;

    public function __construct(
        string $name,
        string $source,
        array $values
    ) {
        $this->name = $name;
        $this->source = $source;
        $this->values = $values;
    }
}
