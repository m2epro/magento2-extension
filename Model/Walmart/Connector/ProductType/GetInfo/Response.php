<?php

namespace Ess\M2ePro\Model\Walmart\Connector\ProductType\GetInfo;

class Response
{
    private string $title;
    private string $nick;
    private array $variationAttributes;
    private array $attributes;

    public function __construct(
        string $title,
        string $nick,
        array $variationAttributes,
        array $attributes
    ) {
        $this->attributes = $attributes;
        $this->title = $title;
        $this->nick = $nick;
        $this->variationAttributes = $variationAttributes;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getNick(): string
    {
        return $this->nick;
    }

    /**
     * @return string[]
     */
    public function getVariationAttributes(): array
    {
        return $this->variationAttributes;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
