<?php

namespace Ess\M2ePro\Model\Amazon\Template;

use Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType as ProductTypeResource;

class ProductType extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public const FIELD_NOT_CONFIGURED = 0;
    public const FIELD_CUSTOM_VALUE = 1;
    public const FIELD_CUSTOM_ATTRIBUTE = 2;

    public const VIEW_MODE_ALL_ATTRIBUTES = 0;
    public const VIEW_MODE_REQUIRED_ATTRIBUTES = 1;

    public const GENERAL_PRODUCT_TYPE_NICK = 'PRODUCT';

    private \Ess\M2ePro\Model\Amazon\Dictionary\ProductType $dictionary;
    private \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $dictionaryProductTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $dictionaryProductTypeRepository,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
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

    public function _construct(): void
    {
        parent::_construct();
        $this->_init(ProductTypeResource::class);
    }

    public function getDictionaryProductTypeId(): int
    {
        return (int)$this->getData(ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID);
    }

    public function getDictionary(): \Ess\M2ePro\Model\Amazon\Dictionary\ProductType
    {
        if (!isset($this->dictionary)) {
            $this->dictionary = $this->dictionaryProductTypeRepository->get(
                $this->getDictionaryProductTypeId()
            );
        }

        return $this->dictionary;
    }

    public function getMarketplaceId(): int
    {
        return $this->getDictionary()->getMarketplaceId();
    }

    public function getNick(): string
    {
        return $this->getDictionary()->getNick();
    }

    public function getTitle(): ?string
    {
        return $this->getData(ProductTypeResource::COLUMN_TITLE);
    }

    public function getCustomAttributesName(): array
    {
        $specifics = $this->getSelfSetting();
        $customAttributes = [];
        foreach ($specifics as $values) {
            foreach ($values as $value) {
                if (!isset($value['mode'])) {
                    continue;
                }

                if ((int)$value['mode'] === self::FIELD_CUSTOM_ATTRIBUTE) {
                    $customAttributes[] = $value['attribute_code'];
                }
            }
        }

        return array_unique($customAttributes);
    }

    public function getCustomAttributesList(): array
    {
        $result = [];
        foreach ($this->getCustomAttributes() as $attributeName => $values) {
            foreach ($values as $value) {
                if ((int)$value['mode'] !== self::FIELD_CUSTOM_ATTRIBUTE) {
                    continue;
                }

                $result[] = [
                    'name' => $attributeName,
                    'attribute_code' => $value['attribute_code']
                ];
            }
        }

        return $result;
    }

    private function getCustomAttributes(): array
    {
        $specifics = $this->getSelfSetting();

        $filterCallback = static function (array $values) {
            foreach ($values as $value) {
                if (!isset($value['mode'])) {
                    continue;
                }

                return (int)$value['mode'] === self::FIELD_CUSTOM_ATTRIBUTE;
            }

            return false;
        };

        return array_filter($specifics, $filterCallback);
    }

    public function getViewMode(): int
    {
        $viewMode = $this->getData(ProductTypeResource::COLUMN_VIEW_MODE);
        if ($viewMode === null) {
            return self::VIEW_MODE_REQUIRED_ATTRIBUTES;
        }

        return (int)$viewMode;
    }

    public function getSelfSetting(): array
    {
        $value = $this->getData(ProductTypeResource::COLUMN_SETTINGS);
        if (empty($value)) {
            return [];
        }

        return (array)json_decode($value, true);
    }
}
