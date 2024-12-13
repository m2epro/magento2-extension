<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes\Provider;

class MagentoAttributeOptionLoader
{
    private \Magento\Catalog\Model\Product\Attribute\Repository $attributeRepository;

    public function __construct(
        \Magento\Catalog\Model\Product\Attribute\Repository $attributeRepository
    ) {
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @param string $attributeCode
     *
     * @return array{array{value:string, label:string}}
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getOptionsMagento(string $attributeCode): array
    {
        $attribute = $this->attributeRepository->get($attributeCode);
        /** @var \Magento\Eav\Model\Entity\Attribute\Source\Table $source */
        $source = $attribute->getSource();

        return $source->getAllOptions(false);
    }
}
