<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product;

class ChangeIdentifierTracker
{
    private const SIZE_CHUNK_ADDED_INSTRUCTIONS = 1000;

    public const INSTRUCTION_TYPE_PRODUCT_IDENTIFIER_CONFIG_CHANGED = 'product_identifier_config_changed';
    private const INSTRUCTION_INITIATOR = 'change_listing_product_identifier_tracker';
    private const INSTRUCTION_PRIORITY = 30; // For listed products

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Configuration */
    private $ebayConfigHelper;
    /** @var ChangeIdentifierTracker\Repository */
    private $repository;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction */
    private $instructionResource;

    /** @var string|null */
    private $startUpcAttribute;
    /** @var string|null */
    private $startEanAttribute;
    /** @var string|null */
    private $startIsbnAttribute;
    /** @var string|null */
    private $startEpidAttribute;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Configuration $ebayConfigHelper,
        ChangeIdentifierTracker\Repository $repository,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource
    ) {
        $this->ebayConfigHelper = $ebayConfigHelper;
        $this->repository = $repository;
        $this->instructionResource = $instructionResource;
    }

    public function startCheckChangeIdentifier(): void
    {
        $this->startUpcAttribute = $this->ebayConfigHelper->getUpcCustomAttribute();
        $this->startEanAttribute = $this->ebayConfigHelper->getEanCustomAttribute();
        $this->startIsbnAttribute = $this->ebayConfigHelper->getIsbnCustomAttribute();
        $this->startEpidAttribute = $this->ebayConfigHelper->getEpidCustomAttribute();
    }

    public function tryCreateInstructionsForChange(): void
    {
        if (!$this->isConfigChanged()) {
            return;
        }

        $syncTemplateIds = $this->repository->getIdsOfSyncTemplatesWithEnabledReviseProductIds();
        if (empty($syncTemplateIds)) {
            return;
        }

        $listingIds = $this->repository->getIdsOfListingsBySyncTemplateIds($syncTemplateIds);
        $listingProductIdsByListings = $this->repository->getIDsOfListedListingProductsByListingIds($listingIds);
        $listingProductIdsBySyncTemplates = $this->repository->getIDsOfListedListingProductsBySyncTemplateIds(
            $syncTemplateIds
        );

        $listingProductIds = array_merge($listingProductIdsByListings, $listingProductIdsBySyncTemplates);
        $listingProductIds = array_unique($listingProductIds);

        $this->addInstructions($listingProductIds);
    }

    private function isConfigChanged(): bool
    {
        return $this->startUpcAttribute !== $this->ebayConfigHelper->getUpcCustomAttribute()
            || $this->startEanAttribute !== $this->ebayConfigHelper->getEanCustomAttribute()
            || $this->startIsbnAttribute !== $this->ebayConfigHelper->getIsbnCustomAttribute()
            || $this->startEpidAttribute !== $this->ebayConfigHelper->getEpidCustomAttribute();
    }

    /**
     * @param int[] $listingProductIds
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function addInstructions(array $listingProductIds): void
    {
        foreach (array_chunk($listingProductIds, self::SIZE_CHUNK_ADDED_INSTRUCTIONS) as $chunk) {
            $instructionsData = [];
            foreach ($chunk as $listingProductId) {
                $instructionsData[] = [
                    'listing_product_id' => $listingProductId,
                    'type' => self::INSTRUCTION_TYPE_PRODUCT_IDENTIFIER_CONFIG_CHANGED,
                    'initiator' => self::INSTRUCTION_INITIATOR,
                    'priority' => self::INSTRUCTION_PRIORITY,
                ];
            }

            $this->instructionResource->add($instructionsData);
        }
    }
}
