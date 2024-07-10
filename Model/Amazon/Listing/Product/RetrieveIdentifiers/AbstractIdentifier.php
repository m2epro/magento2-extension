<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product\RetrieveIdentifiers;

abstract class AbstractIdentifier
{
    protected string $identifier;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    abstract public function hasResolvedType(): bool;

    public function hasUnresolvedType(): bool
    {
        return !$this->hasResolvedType();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function __toString()
    {
        return $this->getIdentifier();
    }
}
