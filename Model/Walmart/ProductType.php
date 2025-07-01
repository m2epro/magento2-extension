<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart;

use Ess\M2ePro\Model\ResourceModel\Walmart\ProductType as ProductTypeResource;

class ProductType extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public const FIELD_NOT_CONFIGURED = 0;
    public const FIELD_CUSTOM_VALUE = 1;
    public const FIELD_CUSTOM_ATTRIBUTE = 2;

    private Dictionary\ProductType\Repository $dictionaryProductTypeRepository;

    public function __construct(
        Dictionary\ProductType\Repository $dictionaryProductTypeRepository,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->dictionaryProductTypeRepository = $dictionaryProductTypeRepository;
    }

    public function _construct()
    {
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Walmart\ProductType::class);
    }

    public function getTitle(): string
    {
        return $this->getDataByKey(ProductTypeResource::COLUMN_TITLE);
    }

    public function getNick(): string
    {
        return $this->getDictionary()->getNick();
    }

    public function getDictionaryId(): int
    {
        return (int)$this->getDataByKey(ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID);
    }

    public function getMarketplaceId(): int
    {
        return $this->getDictionary()->getMarketplaceId();
    }

    /**
     * @return string[]
     */
    public function getVariationAttributes(): array
    {
        return $this->getDictionary()->getVariationAttributes();
    }

    public function getDictionary(): Dictionary\ProductType
    {
        return $this->dictionaryProductTypeRepository->get(
            $this->getDictionaryId()
        );
    }

    /**
     * @return ProductType\AttributeSetting[]
     */
    public function getAttributesSettings(): array
    {
        $settings = [];
        foreach ($this->getRawAttributesSettings() as $attributeName => $values) {
            $attributeSetting = new ProductType\AttributeSetting($attributeName);
            foreach ($values as $value) {
                if ($value['mode'] === self::FIELD_CUSTOM_VALUE) {
                    $attributeSetting->addValue(
                        ProductType\AttributeSetting\Value::createAsCustom($value['value'])
                    );
                    continue;
                }
                if ($value['mode'] === self::FIELD_CUSTOM_ATTRIBUTE) {
                    $attributeSetting->addValue(
                        ProductType\AttributeSetting\Value::createAsProductAttributeCode($value['attribute_code'])
                    );
                }
            }
            $settings[] = $attributeSetting;
        }

        return $settings;
    }

    public function getRawAttributesSettings(): array
    {
        return \Ess\M2ePro\Helper\Json::decode(
            $this->getDataByKey(ProductTypeResource::COLUMN_ATTRIBUTES_SETTINGS)
        );
    }
}
