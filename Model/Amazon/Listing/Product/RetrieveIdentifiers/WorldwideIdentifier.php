<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product\RetrieveIdentifiers;

use Ess\M2ePro\Helper\Data\Product\Identifier;

class WorldwideIdentifier extends AbstractIdentifier
{
    public function isUPC(): bool
    {
        return Identifier::isUPC($this->identifier);
    }

    public function isEAN(): bool
    {
        return Identifier::isEAN($this->identifier);
    }

    public function hasResolvedType(): bool
    {
        return $this->isUPC() || $this->isEAN();
    }
}
