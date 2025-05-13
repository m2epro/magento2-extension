<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate;

class AttributesProcessor
{
    private \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\AttributesProcessor\CreateService $createService;
    private \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\AttributesProcessor\UpdateService $updateService;
    private \Magento\Catalog\Model\ResourceModel\Product $productResource;
    private \Magento\ConfigurableProduct\Helper\Product\Options\Factory $optionsFactory;
    private \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\AttributesProcessor\CreateService $createService,
        \Ess\M2ePro\Model\Ebay\Listing\Other\ProductCreate\AttributesProcessor\UpdateService $updateService,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Catalog\Api\ProductAttributeRepositoryInterface $productAttributeRepository,
        \Magento\ConfigurableProduct\Helper\Product\Options\Factory $optionsFactory
    ) {
        $this->productResource = $productResource;
        $this->optionsFactory = $optionsFactory;
        $this->createService = $createService;
        $this->updateService = $updateService;
        $this->productAttributeRepository = $productAttributeRepository;
    }

    public function setSuperAttributesToProduct(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        ChannelItem $channelItem
    ): \Magento\Catalog\Api\Data\ProductInterface {
        $optionsData = [];
        $extensionAttributes = $product->getExtensionAttributes();
        /**
         * @var ChannelAttributeItem $channelAttribute
         */
        foreach ($channelItem->getVariationSet() as $channelAttribute) {
            $attributeCode = $channelAttribute->getAttributeCode();
            try {
                $attribute = $this->productAttributeRepository->get($attributeCode);
                $this->updateService->updateAttribute(
                    $attribute,
                    $channelAttribute
                );
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $attribute = $this->createService->createMagentoAttribute($channelAttribute);
            }

            $optionsData[] = [
                'code' => $attribute->getAttributeCode(),
                'attribute_id' => $attribute->getAttributeId(),
                'label' => $attribute->getDefaultFrontendLabel(),
                'values' => $this->prepareConfigurableOptions($attribute->getOptions()),
            ];
        }

        $configurableOptions = [];
        if (!empty($optionsData)) {
            $configurableOptions = $this->optionsFactory->create($optionsData);
        }

        $extensionAttributes->setConfigurableProductOptions($configurableOptions);
        $product->setExtensionAttributes($extensionAttributes);

        return $product;
    }

    public function setOptionAttributeToChild(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        ChannelItem $channelItem
    ): void {
        foreach ($channelItem->getSpecifics() as $attributeCode => $value) {
            $attribute = $this->productResource->getAttribute($attributeCode);
            $optionId = $attribute->getSource()->getOptionId($value);
            if ($optionId) {
                $product->setData($attributeCode, $optionId);
                $this->productResource->save($product);
            }
        }
    }

    private function prepareConfigurableOptions(array $optionsData): array
    {
        $configurableOptions = [];
        foreach ($optionsData as $optionObject) {
            if ($optionObject->getValue()) {
                $configurableOptions[(int)$optionObject->getValue()] = [
                    'value_index' => $optionObject->getValue(),
                    'include' => '1',
                ];
            }
        }

        return $configurableOptions;
    }
}
