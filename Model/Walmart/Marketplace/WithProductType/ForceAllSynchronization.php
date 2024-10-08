<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Marketplace\WithProductType;

class ForceAllSynchronization
{
    private \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository;
    private \Ess\M2ePro\Model\Walmart\Dictionary\CategoryService $categoryDictionaryService;
    private \Ess\M2ePro\Model\Walmart\Dictionary\MarketplaceService $marketplaceDictionaryService;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository,
        \Ess\M2ePro\Model\Walmart\Dictionary\CategoryService $categoryDictionaryService,
        \Ess\M2ePro\Model\Walmart\Dictionary\MarketplaceService $marketplaceDictionaryService
    ) {
        $this->marketplaceRepository = $marketplaceRepository;
        $this->categoryDictionaryService = $categoryDictionaryService;
        $this->marketplaceDictionaryService = $marketplaceDictionaryService;
    }

    public function process(): void
    {
        foreach ($this->marketplaceRepository->findActive() as $marketplace) {
            if (
                !$marketplace->getChildObject()
                             ->isSupportedProductType()
            ) {
                continue;
            }

            $this->marketplaceDictionaryService->update($marketplace);
            $this->categoryDictionaryService->update($marketplace);
        }
    }
}
