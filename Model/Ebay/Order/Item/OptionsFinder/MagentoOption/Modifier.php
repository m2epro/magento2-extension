<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Order\Item\OptionsFinder\MagentoOption;

use Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Option;
use Ess\M2ePro\Model\Magento\Product\Variation\RawSuite;

class Modifier
{
    private \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService $groupedService;
    private \Ess\M2ePro\Model\Magento\ProductFactory $productFactory;
    private \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $listingProductRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService $groupedService,
        \Ess\M2ePro\Model\Magento\ProductFactory $productFactory,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Repository $listingProductRepository
    ) {
        $this->groupedService = $groupedService;
        $this->productFactory = $productFactory;
        $this->listingProductRepository = $listingProductRepository;
    }

    public function modify(
        \Ess\M2ePro\Model\Magento\Product\Variation\RawSuite $rawSuite,
        ?\Ess\M2ePro\Model\Ebay\Item $channelItem = null
    ): void {
        if ($rawSuite->isGrouped()) {
            $this->modifyGrouped($rawSuite);

            return;
        }

        if ($channelItem !== null) {
            $this->appendReplacedOptions($rawSuite, $channelItem);
        }

        $this->modifyOtherTypes($rawSuite);
    }

    private function appendReplacedOptions(
        RawSuite $rawSuite,
        \Ess\M2ePro\Model\Ebay\Item $channelItem
    ): void {
        $ebayListingProduct = $this->listingProductRepository
            ->findByItemId((string)$channelItem->getItemId());

        if ($ebayListingProduct === null) {
            return;
        }

        $replacedOptions = $ebayListingProduct->getReplacedVariationOptions();
        if (empty($replacedOptions)) {
            return;
        }

        $options = $rawSuite->getOptions();
        foreach ($options as &$attribute) {
            foreach ($attribute['values'] as &$option) {
                foreach ($replacedOptions as $replacedOption) {
                    if (
                        in_array($replacedOption->attribute, $attribute['labels'])
                        && in_array($replacedOption->option, $option['labels'])
                    ) {
                        $option['labels'][] = $replacedOption->channelOption;
                    }
                }
            }
        }

        $rawSuite->setOptions($options);
    }

    private function modifyGrouped(\Ess\M2ePro\Model\Magento\Product\Variation\RawSuite $rawSuite): void
    {
        $newOptions = [];

        $attributeCodeOfOptionTitle = $this->findProductAttributeCodeOfOptionTitle();
        foreach ($rawSuite->getOptions() as $product) {
            $title = null;
            if ($attributeCodeOfOptionTitle !== null) {
                $title = $this->getProductAttributeValue(
                    $attributeCodeOfOptionTitle,
                    $product
                );
            }

            $name = Option::formatOptionValue($title ?? $product->getName());
            $product->setName($name);

            $newOptions[] = $product;
        }

        $rawSuite->setOptions($newOptions);
    }

    private function findProductAttributeCodeOfOptionTitle(): ?string
    {
        $mapping = $this->groupedService->findConfiguredVariationOptionTitle();
        if ($mapping === null) {
            return null;
        }

        return $mapping->value;
    }

    private function getProductAttributeValue(string $attributeCode, \Magento\Catalog\Model\Product $product): ?string
    {
        $magentoProduct = $this->productFactory->create();
        $magentoProduct->loadProduct(
            $product->getId(),
            $product->getStoreId()
        );

        $value = $magentoProduct->getAttributeValue($attributeCode);

        return !empty($value) ? $value : null;
    }

    private function modifyOtherTypes(\Ess\M2ePro\Model\Magento\Product\Variation\RawSuite $rawSuite): void
    {
        $options = $rawSuite->getOptions();

        foreach ($options as &$option) {
            foreach ($option['values'] as &$value) {
                foreach ($value['labels'] as &$label) {
                    $label = Option::formatOptionValue((string)$label);
                }
            }
        }

        if (isset($options['additional']['attributes'])) {
            foreach ($options['additional']['attributes'] as $code => &$title) {
                $title = trim($title);
            }
            unset($title);
        }

        $rawSuite->setOptions($options);
    }
}
