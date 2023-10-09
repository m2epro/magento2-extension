<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping;

class Manager
{
    /** @var \Ess\M2ePro\Model\Amazon\ProductType\AttributeMappingFactory */
    private $attributeMappingFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping */
    private $attributeMappingResource;
    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductType */
    private $productType;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping\CollectionFactory */
    private $collectionFactory;
    /** @var \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping[] */
    private $attributeMappingModels = [];

    public function __construct(
        \Ess\M2ePro\Model\Amazon\ProductType\AttributeMappingFactory $attributeMappingFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping $attributeMappingResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping\CollectionFactory $collectionFactory,
        \Ess\M2ePro\Model\Amazon\Template\ProductType $productType
    ) {
        $this->attributeMappingFactory = $attributeMappingFactory;
        $this->attributeMappingResource = $attributeMappingResource;
        $this->collectionFactory = $collectionFactory;
        $this->productType = $productType;
    }

    public function createNewMappings(): void
    {
        $this->prepareAttributeMapModels();

        foreach ($this->attributeMappingModels as $model) {
            if ($model->isObjectNew() === false) {
                continue;
            }

            $this->attributeMappingResource->save($model);
        }
    }

    public function updateMappings(): void
    {
        $this->prepareAttributeMapModels();

        foreach ($this->attributeMappingModels as $model) {
            $this->attributeMappingResource->save($model);
        }
    }

    public function hasChangedMappings(): bool
    {
        foreach ($this->attributeMappingModels as $model) {
            if ($model->hasDataChanges()) {
                return true;
            }
        }

        return false;
    }

    private function prepareAttributeMapModels(): void
    {
        $productTypeCustomAttributes = $this->productType->getCustomAttributesList();
        $customProductTypeAttributes = array_column($productTypeCustomAttributes, 'name');

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(
            'product_type_attribute_code',
            ['in' => $customProductTypeAttributes]
        );

        $itemsByProductTypeAttributeCode = [];
        /** @var \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping $item */
        foreach ($collection->getItems() as $item) {
            $itemsByProductTypeAttributeCode[$item->getProductTypeAttributeCode()] = $item;
        }

        foreach ($productTypeCustomAttributes as $customAttribute) {
            $productTypeAttributeCode = $customAttribute['name'];
            $magentoAttributeCode = $customAttribute['attribute_code'];

            if (array_key_exists($productTypeAttributeCode, $itemsByProductTypeAttributeCode)) {
                $model = $itemsByProductTypeAttributeCode[$productTypeAttributeCode];
                $model->setDataChanges(false);
                $model->setMagentoAttributeCode($magentoAttributeCode);
            } else {
                $model = $this->createNewMapping(
                    $productTypeAttributeCode,
                    $magentoAttributeCode
                );
            }

            $this->attributeMappingModels[] = $model;
        }
    }

    private function createNewMapping(
        string $productTypeAttributeCode,
        string $magentoAttributeCode
    ): \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping {
        $newMappingModel = $this->attributeMappingFactory->create();
        $newMappingModel->setProductTypeAttributeCode($productTypeAttributeCode);
        $newMappingModel->setMagentoAttributeCode($magentoAttributeCode);
        $newMappingModel->setProductTypeAttributeName(
            $this->getProductTypeAttributeName($productTypeAttributeCode)
        );

        return $newMappingModel;
    }

    private function getProductTypeAttributeName(string $productTypeAttributeCode): string
    {
        return $this->productType
            ->getDictionary()
            ->findNameByProductTypeCode($productTypeAttributeCode);
    }
}
