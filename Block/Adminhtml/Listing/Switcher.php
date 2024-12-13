<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing;

class Switcher extends \Ess\M2ePro\Block\Adminhtml\Switcher
{
    protected $paramName = 'id';
    private \Ess\M2ePro\Model\Amazon\Listing\Repository $amazonListingRepository;
    private \Ess\M2ePro\Model\Ebay\Listing\Repository $ebayListingRepository;
    private \Ess\M2ePro\Model\Walmart\Listing\Repository $walmartListingRepository;
    private string $componentMode;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Model\Amazon\Listing\Repository $amazonListingRepository,
        \Ess\M2ePro\Model\Ebay\Listing\Repository $ebayListingRepository,
        \Ess\M2ePro\Model\Walmart\Listing\Repository $walmartListingRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->amazonListingRepository = $amazonListingRepository;
        $this->ebayListingRepository = $ebayListingRepository;
        $this->walmartListingRepository = $walmartListingRepository;
        $this->componentMode = $data['component_mode'] ?? '';
    }

    public function getLabel(): string
    {
        return (string)__('Switch Listing');
    }

    public function hasDefaultOption(): bool
    {
        return false;
    }

    protected function loadItems(): void
    {
        $listings = $this->getListingCollection();

        if (empty($listings)) {
            $this->items = [];

            return;
        }

        if (count($listings) < 2) {
            $this->hasDefaultOption = false;
            $this->setIsDisabled(true);
        }

        $items = [];
        foreach ($listings as $listing) {
            $listingTitle = $this->filterManager->truncate(
                $listing->getTitle(),
                ['length' => 50]
            );

            $items[] = [
                'value' => $listing->getId(),
                'label' => $listingTitle,
            ];
        }

        $this->items = ['mode' => ['value' => $items]];
    }

    /**
     * @return \Ess\M2ePro\Model\Listing[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getListingCollection(): array
    {
        switch ($this->componentMode) {
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                return $this->amazonListingRepository->getAll();
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                return $this->ebayListingRepository->getAll();
            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                return $this->walmartListingRepository->getAll();
            default:
                throw new \Ess\M2ePro\Model\Exception\Logic('Invalid component mode: ' . $this->componentMode);
        }
    }
}
