<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Model\Listing;

class DeleteService
{
    private const LIFETIME_ONE_WEEK_IN_DAYS = 7;

    private Repository $wizardRepository;

    public function __construct(Repository $wizardRepository)
    {
        $this->wizardRepository = $wizardRepository;
    }

    public function removeOld(): void
    {
        $borderDate = \Ess\M2ePro\Helper\Date::createCurrentGmt()
                                               ->modify(sprintf('- %d days', self::LIFETIME_ONE_WEEK_IN_DAYS));

        foreach ($this->wizardRepository->findOldCompleted($borderDate) as $wizard) {
            $this->wizardRepository->remove($wizard);
        }
    }

    public function removeByListing(Listing $listing): void
    {
        foreach ($this->wizardRepository->findWizardsByListing($listing) as $wizard) {
            $this->wizardRepository->remove($wizard);
        }
    }
}
