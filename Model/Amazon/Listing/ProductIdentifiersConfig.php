<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing;

class ProductIdentifiersConfig
{
    private \Ess\M2ePro\Helper\Component\Amazon\Configuration $configuration;

    public function __construct(\Ess\M2ePro\Helper\Component\Amazon\Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function isExistsGeneralIdAttribute(\Ess\M2ePro\Model\Amazon\Listing $amazonListing): bool
    {
        return $this->findGeneralIdAttribute($amazonListing) !== null;
    }

    public function findGeneralIdAttribute(\Ess\M2ePro\Model\Amazon\Listing $amazonListing): ?string
    {
        if ($amazonListing->isExistsGeneralIdAttribute()) {
            return $amazonListing->getGeneralIdAttribute();
        }

        if ($this->configuration->isGeneralIdModeCustomAttribute()) {
            return $this->configuration->getGeneralIdCustomAttribute();
        }

        return null;
    }

    public function isExistsWorldwideAttribute(\Ess\M2ePro\Model\Amazon\Listing $amazonListing): bool
    {
        return $this->findWorldWideIdAttribute($amazonListing) !== null;
    }

    public function findWorldWideIdAttribute(\Ess\M2ePro\Model\Amazon\Listing $amazonListing): ?string
    {
        if ($amazonListing->isExistsWorldWideIdAttribute()) {
            return $amazonListing->getWorldWideIdAttribute();
        }

        if ($this->configuration->isWorldWideIdModeCustomAttribute()) {
            return $this->configuration->getWorldwideCustomAttribute();
        }

        return null;
    }
}
