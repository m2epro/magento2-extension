<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping;

class Replacer
{
    private static array $attributeValueCache = [];

    private \Magento\Bundle\Model\Option $bundleOption;
    private \Magento\Catalog\Model\Product $optionProduct;
    private \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\Repository $mappingRepository;
    private \Ess\M2ePro\Model\Magento\ProductFactory $magentoProductWrapperFactory;

    public function __construct(
        \Magento\Bundle\Model\Option $bundleOption,
        \Magento\Catalog\Model\Product $optionProduct,
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\Repository $mappingRepository,
        \Ess\M2ePro\Model\Magento\ProductFactory $magentoProductWrapperFactory
    ) {
        $this->bundleOption = $bundleOption;
        $this->optionProduct = $optionProduct;
        $this->mappingRepository = $mappingRepository;
        $this->magentoProductWrapperFactory = $magentoProductWrapperFactory;
    }

    public function getName(): string
    {
        $title = $this->bundleOption->getDefaultTitle()
            ?? $this->bundleOption->getTitle()
            ?? '';

        $mapping = $this->mappingRepository->findByTitle($title);

        if ($mapping === null) {
            return $this->getDefaultName();
        }

        $attributeCode = $mapping->getAttributeCode();

        if (empty($attributeCode)) {
            return '';
        }

        $attributeValue = $this->getAttributeValue($this->optionProduct, $attributeCode);

        if (empty($attributeValue)) {
            return '';
        }

        return $attributeValue;
    }

    public function getDefaultName(): string
    {
        return $this->optionProduct->getName();
    }

    private function getAttributeValue(\Magento\Catalog\Model\Product $product, string $attributeCode): string
    {
        $cacheKey = sprintf(
            '%s-%s-%s',
            $product->getId(),
            $product->getStoreId(),
            $attributeCode
        );

        if (isset(self::$attributeValueCache[$cacheKey])) {
            return self::$attributeValueCache[$cacheKey];
        }

        self::$attributeValueCache[$cacheKey] = $this->magentoProductWrapperFactory
            ->create()
            ->setProductId($this->optionProduct->getId())
            ->setStoreId($this->optionProduct->getStoreId())
            ->getAttributeValue($attributeCode);

        return self::$attributeValueCache[$cacheKey];
    }
}
