<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product\RetrieveIdentifiers;

class Identifiers
{
    private ?GeneralIdentifier $generalId = null;
    private ?WorldwideIdentifier $worldwideId = null;

    public function getGeneralId(): ?GeneralIdentifier
    {
        return $this->generalId;
    }

    public function setGeneralId(?GeneralIdentifier $generalId): void
    {
        $this->generalId = $generalId;
    }

    public function getWorldwideId(): ?WorldwideIdentifier
    {
        return $this->worldwideId;
    }

    public function setWorldwideId(?WorldwideIdentifier $worldwideId): void
    {
        $this->worldwideId = $worldwideId;
    }
}
