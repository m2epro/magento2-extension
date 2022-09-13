<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers;

abstract class IdentifierDTO
{
    /** @var string */
    protected $identifier;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return bool
     */
    abstract public function hasResolvedType(): bool;

    /**
     * @return bool
     */
    public function hasUnresolvedType(): bool
    {
        return !$this->hasResolvedType();
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getIdentifier();
    }
}
