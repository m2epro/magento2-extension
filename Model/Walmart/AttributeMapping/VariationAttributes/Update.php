<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes;

use Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product as WalmartListingProductResource;

class Update
{
    private \Ess\M2ePro\Model\AttributeOptionMapping\Repository $attributeMappingRepository;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private WalmartListingProductResource $walmartListingProductResource;
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\AttributeOptionMapping\Repository $attributeMappingRepository,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        WalmartListingProductResource $walmartListingProductResource,
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository
    ) {
        $this->attributeMappingRepository = $attributeMappingRepository;
        $this->resourceConnection = $resourceConnection;
        $this->walmartListingProductResource = $walmartListingProductResource;
        $this->productTypeRepository = $productTypeRepository;
    }

    /**
     * @param \Ess\M2ePro\Model\AttributeOptionMapping\Pair[] $mappings
     *
     * @return int - processed (updated or created) count
     */
    public function process(array $mappings): int
    {
        $existedRows = $this->getExistedMappingGroupedByCode();

        $processedCount = 0;
        $affectedProductTypeDictionaryIds = [];
        foreach ($mappings as $newPair) {
            $exist = $existedRows[$this->groupedKey($newPair)] ?? null;
            if ($exist === null) {
                $this->attributeMappingRepository->create($newPair);
                $affectedProductTypeDictionaryIds[] = $newPair->getProductTypeId();

                $processedCount++;

                continue;
            }

            unset($existedRows[$this->groupedKey($newPair)]);

            if (
                $exist->getMagentoAttributeCode() === $newPair->getMagentoAttributeCode()
                && $exist->getMagentoOptionId() === $newPair->getMagentoOptionId()
                && $exist->getMagentoOptionTitle() === $newPair->getMagentoOptionTitle()
            ) {
                continue;
            }

            $exist->setMagentoAttributeCode($newPair->getMagentoAttributeCode());
            $exist->setMagentoOptionId($newPair->getMagentoOptionId());
            $exist->setMagentoOptionTitle($newPair->getMagentoOptionTitle());

            $this->attributeMappingRepository->save($exist);
            $affectedProductTypeDictionaryIds[] = $exist->getProductTypeId();

            $processedCount++;
        }

        if (!empty($existedRows)) {
            foreach ($existedRows as $someOld) {
                $this->attributeMappingRepository->remove($someOld);
            }
        }

        $this->markProductsAsNeedProcessor($affectedProductTypeDictionaryIds);

        return $processedCount;
    }

    /**
     * @return \Ess\M2ePro\Model\AttributeOptionMapping\Pair[]
     */
    private function getExistedMappingGroupedByCode(): array
    {
        $result = [];

        $existed = $this->attributeMappingRepository->findByComponentAndType(
            \Ess\M2ePro\Helper\Component\Walmart::NICK,
            \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributesService::MAPPING_TYPE
        );
        foreach ($existed as $pair) {
            $result[$this->groupedKey($pair)] = $pair;
        }

        return $result;
    }

    private function groupedKey(\Ess\M2ePro\Model\AttributeOptionMapping\Pair $pair): string
    {
        return implode('_', [
            $pair->getProductTypeId(),
            $pair->getChannelAttributeCode(),
            $pair->getChannelOptionCode(),
        ]);
    }

    private function markProductsAsNeedProcessor(array $affectedProductTypeDictionaryIds): void
    {
        $affectedProductTypeDictionaryIds = array_unique($affectedProductTypeDictionaryIds);

        $productTypes = $this->productTypeRepository
            ->findByDictionaryIds($affectedProductTypeDictionaryIds);

        $productTypeIds = [];
        foreach ($productTypes as $productType) {
            $productTypeIds[] = $productType->getId();
        }

        $connection = $this->resourceConnection->getConnection();

        $connection->update(
            $this->walmartListingProductResource->getMainTable(),
            ['variation_parent_need_processor' => 1],
            [
                'is_variation_parent = ?' => 1,
                sprintf('%s IN (?)', WalmartListingProductResource::COLUMN_PRODUCT_TYPE_ID) => array_unique(
                    $productTypeIds
                ),
            ]
        );
    }
}
