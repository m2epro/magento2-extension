<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product\MultiLocationInventory;

class GetMappedSourcesForProduct
{
    private \Magento\Store\Model\StoreManagerInterface $storeManager;
    private \Ess\M2ePro\Helper\Magento $magentoHelper;
    private \Magento\InventorySalesApi\Api\StockResolverInterface $stockResolver;
    private \Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->stockResolver = $objectManager
            ->get(\Magento\InventorySalesApi\Api\StockResolverInterface::class);
        $this->getSourcesAssignedToStockOrderedByPriority = $objectManager
            ->get(\Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface::class);
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account\MultiLocationInventoryMapping\Item[]
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct): array
    {
        $multiLocationInventoryMapping = $amazonListingProduct
            ->getAmazonAccount()
            ->getMultiLocationInventoryMapping();

        if ($multiLocationInventoryMapping->isEmpty()) {
            return [];
        }

        $listingStoreId = $amazonListingProduct->getListing()->getStoreId();

        return $this->filterByStore($listingStoreId, $multiLocationInventoryMapping->getItems());
    }

    /**
     * @param int $storeId
     * @param \Ess\M2ePro\Model\Amazon\Account\MultiLocationInventoryMapping\Item[] $mappedSources
     *
     * @return \Ess\M2ePro\Model\Amazon\Account\MultiLocationInventoryMapping\Item[]
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function filterByStore(int $storeId, array $mappedSources): array
    {
        $availableSourceCodes = $this->getAvailableSourcesForStore($storeId);

        $result = [];
        foreach ($mappedSources as $source) {
            if (!in_array($source->magentoSourceCode, $availableSourceCodes, true)) {
                continue;
            }

            $result[] = $source;
        }

        return $result;
    }

    /**
     * @return string[]
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getAvailableSourcesForStore(int $storeId): array
    {
        $store = $this->storeManager->getStore($storeId);
        $website = $this->storeManager->getWebsite($store->getWebsiteId());

        $stock = $this->stockResolver->execute(
            \Magento\InventorySalesApi\Api\Data\SalesChannelInterface::TYPE_WEBSITE,
            $website->getCode()
        );

        $sources = $this->getSourcesAssignedToStockOrderedByPriority->execute($stock->getStockId());

        $sourceCodes = [];
        foreach ($sources as $source) {
            $sourceCodes[] = $source->getSourceCode();
        }

        return $sourceCodes;
    }
}
