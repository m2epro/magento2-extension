<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

class MultiLocationInventoryFactory
{
    private \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    private \Ess\M2ePro\Helper\Magento $magentoHelper;
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Ess\M2ePro\Helper\Magento $magentoHelper
    ) {
        $this->objectManager = $objectManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->magentoHelper = $magentoHelper;
    }

    public function create(
        \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct
    ): MultiLocationInventory {
        if (!$this->magentoHelper->isMSISupportingVersion()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Multi-location inventory is not supported.');
        }

        $locations = $this->collectLocationsInventory($amazonListingProduct);

        return new MultiLocationInventory($locations);
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\MultiLocationInventory\LocationInventory[]
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function collectLocationsInventory(
        \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct
    ): array {
        $productMappedSources = $this->objectManager
            ->get(\Ess\M2ePro\Model\Amazon\Listing\Product\MultiLocationInventory\GetMappedSourcesForProduct::class)
            ->execute($amazonListingProduct);
        $magentoProduct = $amazonListingProduct->getMagentoProduct();

        if (empty($productMappedSources)) {
            return [];
        }

        $locationsData = [];
        foreach ($productMappedSources as $source) {
            if (!isset($locationsData[$source->amazonLocationCode])) {
                $locationsData[$source->amazonLocationCode] = [
                    'code' => $source->amazonLocationCode,
                    'title' => $source->amazonLocationTitle,
                    'qty' => 0,
                ];
            }

            $locationsData[$source->amazonLocationCode]['qty'] += $this->getQtyBySource(
                $magentoProduct->getSku(),
                $source->magentoSourceCode
            );
        }

        $locations = [];
        foreach ($locationsData as $locationData) {
            $locations[] = new \Ess\M2ePro\Model\Amazon\Listing\Product\MultiLocationInventory\LocationInventory(
                $locationData['code'],
                $locationData['title'],
                $locationData['qty']
            );
        }

        return $locations;
    }

    private function getQtyBySource(string $sku, string $sourceCode): int
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', ['eq' => $sku])
            ->addFilter('source_code', ['eq' => $sourceCode])
            ->create();

        $sourceItems = $this->objectManager
            ->get(\Magento\InventoryApi\Api\SourceItemRepositoryInterface::class)
            ->getList($searchCriteria)
            ->getItems();

        if (\count($sourceItems) === 0) {
            return 0;
        }

        /** @var \Magento\InventoryApi\Api\Data\SourceItemInterface $firstSourceItem */
        $firstSourceItem = reset($sourceItems);

        return (int)$firstSourceItem->getQuantity();
    }
}
