<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Listing\Ui;

use Ess\M2ePro\Model\Listing;

class RuntimeStorage
{
    private Listing $listing;

    public function hasListing(): bool
    {
        return isset($this->listing);
    }

    public function setListing(Listing $listing): void
    {
        $this->listing = $listing;
    }

    public function getListing(): ?Listing
    {
        return $this->listing;
    }
}
