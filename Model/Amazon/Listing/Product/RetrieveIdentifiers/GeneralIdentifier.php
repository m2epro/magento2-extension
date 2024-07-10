<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product\RetrieveIdentifiers;

use Ess\M2ePro\Helper\Data\Product\Identifier;

class GeneralIdentifier extends AbstractIdentifier
{
    public function isASIN(): bool
    {
        return Identifier::isASIN($this->identifier);
    }

    public function isISBN(): bool
    {
        return Identifier::isISBN($this->identifier);
    }

    public function hasResolvedType(): bool
    {
        return $this->isASIN() || $this->isISBN();
    }
}
