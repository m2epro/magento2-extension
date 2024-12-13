<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes;

use Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider\AttributeOption;
use Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider\MagentoAttributeOptionLoader;
use Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider\ProductType;
use Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider\VariationAttribute;

class Provider
{
    private bool $existMappingsLoaded = false;
    private array $existedMappings = [];

    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType $productTypeResource;
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType $productTypeDictionaryResource;
    /** @var \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\MagentoAttributeFinder */
    private MagentoAttributeFinder $attributeFinder;
    /** @var \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider\MagentoAttributeOptionLoader */
    private Provider\MagentoAttributeOptionLoader $attributeOptionLoader;
    private \Ess\M2ePro\Model\AttributeOptionMapping\Repository $attributeOptionRepository;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Walmart\ProductType $productTypeResource,
        \Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary\ProductType $productTypeDictionaryResource,
        MagentoAttributeFinder $attributeFinder,
        MagentoAttributeOptionLoader $attributeOptionLoader,
        \Ess\M2ePro\Model\AttributeOptionMapping\Repository $attributeOptionRepository
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productTypeResource = $productTypeResource;
        $this->productTypeDictionaryResource = $productTypeDictionaryResource;
        $this->attributeFinder = $attributeFinder;
        $this->attributeOptionLoader = $attributeOptionLoader;
        $this->attributeOptionRepository = $attributeOptionRepository;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider\ProductType[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAll(): array
    {
        $productTypesData = $this->loadAllProductTypesData();
        if (empty($productTypesData)) {
            return [];
        }

        $resultProductTypes = [];
        foreach ($productTypesData as $productTypeData) {
            $variationAttributes = $this->getVariationAttributes(
                (int)$productTypeData['product_type_id'],
                \Ess\M2ePro\Helper\Json::decode($productTypeData['product_type_attributes']),
                \Ess\M2ePro\Helper\Json::decode($productTypeData['product_type_variation_attributes'])
            );

            if (empty($variationAttributes)) {
                continue;
            }

            $resultProductTypes[] = new ProductType(
                (int)$productTypeData['product_type_id'],
                $productTypeData['product_type_title'],
                $variationAttributes
            );
        }

        return $resultProductTypes;
    }

    private function loadAllProductTypesData(): array
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from(
            ['user_product_type' => $this->productTypeResource->getMainTable()],
            [
                'product_type_title' => 'user_product_type.title',
                'user_product_type.attributes_settings',
            ]
        );
        $select->joinInner(
            ['dictionary_product_type' => $this->productTypeDictionaryResource->getMainTable()],
            'dictionary_product_type.id = user_product_type.dictionary_product_type_id',
            [
                'product_type_id' => 'dictionary_product_type.id',
                'product_type_attributes' => 'dictionary_product_type.attributes',
                'product_type_variation_attributes' => 'dictionary_product_type.variation_attributes',
            ]
        );

        return $select->query()->fetchAll();
    }

    /**
     * @return VariationAttribute[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getVariationAttributes(
        int $productTypeId,
        array $productTypeAttributes,
        array $variationAttributeNames
    ): array {
        $existedMappings = $this->getExistedMappings();

        $variationAttributes = [];
        foreach ($productTypeAttributes as $productTypeAttribute) {
            $channelAttributeName = str_replace('#array', '', $productTypeAttribute['name']);

            if (!in_array($channelAttributeName, $variationAttributeNames)) {
                continue;
            }

            if (empty($productTypeAttribute['options'] ?? [])) {
                continue;
            }

            $channelAttributeTitle = $productTypeAttribute['title'];
            $magentoAttribute = $this->attributeFinder->findMagentoAttribute([
                $channelAttributeName,
                $channelAttributeTitle,
            ]);

            if ($magentoAttribute === null) {
                continue;
            }

            $channelAttributeOptions = [];
            foreach ($productTypeAttribute['options'] ?? [] as $optionName => $optionValue) {
                $key = implode('_', [
                    $productTypeId,
                    $channelAttributeName,
                    $optionName,
                    $magentoAttribute['attribute_code'],
                ]);

                $selectedOptionId = $existedMappings[$key] ?? null;

                $channelAttributeOptions[] = new AttributeOption($optionName, $optionValue, $selectedOptionId);
            }

            $variationAttributes[] = new VariationAttribute(
                $channelAttributeName,
                $channelAttributeTitle,
                $channelAttributeOptions,
                $magentoAttribute['attribute_code'],
                $magentoAttribute['attribute_label'],
                $this->attributeOptionLoader->getOptionsMagento(
                    $magentoAttribute['attribute_code']
                ),
            );
        }

        return $variationAttributes;
    }

    private function getExistedMappings(): array
    {
        if (!$this->existMappingsLoaded) {
            $existed = $this->attributeOptionRepository->findByComponentAndType(
                \Ess\M2ePro\Helper\Component\Walmart::NICK,
                \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributesService::MAPPING_TYPE
            );

            foreach ($existed as $pair) {
                $this->existedMappings[$this->groupedKey($pair)] = $pair->getMagentoOptionId();
            }
            $this->existMappingsLoaded = true;
        }

        return $this->existedMappings;
    }

    private function groupedKey(\Ess\M2ePro\Model\AttributeOptionMapping\Pair $pair): string
    {
        return implode('_', [
            $pair->getProductTypeId(),
            $pair->getChannelAttributeCode(),
            $pair->getChannelOptionCode(),
            $pair->getMagentoAttributeCode(),
        ]);
    }
}
