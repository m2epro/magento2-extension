<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider;

class ProductType
{
    private int $id;
    private string $title;
    private array $variationAttributes;

    public function __construct(
        int $id,
        string $title,
        array $variationAttributes
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->variationAttributes = $variationAttributes;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider\VariationAttribute[]
     */
    public function getVariationAttributes(): array
    {
        return $this->variationAttributes;
    }
}
