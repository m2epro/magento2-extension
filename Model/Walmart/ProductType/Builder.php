<?php

namespace Ess\M2ePro\Model\Walmart\ProductType;

use Ess\M2ePro\Model\ResourceModel\Walmart\ProductType as ProductTypeResource;

class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    private \Ess\M2ePro\Model\Walmart\Dictionary\ProductType\Repository $productTypeDictionaryRepository;
    private \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository;
    private array $attributesSettings = [];

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Dictionary\ProductType\Repository $productTypeDictionaryRepository,
        \Ess\M2ePro\Model\Walmart\Marketplace\Repository $marketplaceRepository,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->productTypeDictionaryRepository = $productTypeDictionaryRepository;
        $this->marketplaceRepository = $marketplaceRepository;
    }

    protected function prepareData()
    {
        if ($this->model->getId()) {
            $data = $this->model->getData();
        } else {
            $data = $this->getDefaultData();

            $temp = [];
            $keys = ['marketplace_id', 'nick'];
            foreach ($keys as $key) {
                if (empty($this->rawData['general'][$key])) {
                    throw new \Ess\M2ePro\Model\Exception(
                        "Missing required field for Product Type: $key"
                    );
                }

                $temp[$key] = $this->rawData['general'][$key];
            }

            $marketplace = $this->marketplaceRepository->get((int)$temp['marketplace_id']);
            if (
                !$marketplace->getChildObject()
                             ->isSupportedProductType()
            ) {
                throw new \Ess\M2ePro\Model\Exception\Logic('Marketplace not supported Product Types');
            }

            $dictionary = $this->productTypeDictionaryRepository->findByNick(
                $temp['nick'],
                (int)$temp['marketplace_id']
            );
            if ($dictionary === null) {
                throw new \Ess\M2ePro\Model\Exception(
                    "Product Type data not found for provided marketplace_id and product type nick"
                );
            }

            $data[ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID] = $dictionary->getId();
        }

        if (isset($this->rawData['general']['product_type_title'])) {
            $data[ProductTypeResource::COLUMN_TITLE] = $this->rawData['general']['product_type_title'];
        }

        if (!empty($this->rawData['field_data']) && is_array($this->rawData['field_data'])) {
            $this->attributesSettings = [];
            $this->collectAttributesSettings($this->rawData['field_data']);

            $data[ProductTypeResource::COLUMN_ATTRIBUTES_SETTINGS] = \Ess\M2ePro\Helper\Json::encode(
                $this->attributesSettings
            );
        }

        return $data;
    }

    private function collectAttributesSettings(array $data, array $path = []): void
    {
        $pathString = implode("/", $path);
        foreach ($data as $key => $value) {
            if (isset($value['mode']) && is_string($value['mode'])) {
                if (!isset($this->attributesSettings[$pathString])) {
                    $this->attributesSettings[$pathString] = [];
                }

                if ($fieldData = $this->collectFieldData($value, $path)) {
                    $this->attributesSettings[$pathString][] = $fieldData;
                }
            } else {
                $currentPath = $path;
                $currentPath[] = $key;
                $this->collectAttributesSettings($value, $currentPath);
            }
        }

        if (empty($this->attributesSettings[$pathString])) {
            unset($this->attributesSettings[$pathString]);
        }
    }

    private function collectFieldData(array $field): array
    {
        if (empty($field['mode'])) {
            return [];
        }

        switch ((int)$field['mode']) {
            case \Ess\M2ePro\Model\Walmart\ProductType::FIELD_CUSTOM_VALUE:
                if (!empty($field['format']) && $field['format'] === 'date-time') {
                    $timestamp = \Ess\M2ePro\Helper\Date::createDateInCurrentZone($field['value'])->getTimestamp();
                    $datetime = \Ess\M2ePro\Helper\Date::createCurrentGmt();
                    $datetime->setTimestamp($timestamp);

                    $field['value'] = $datetime->format('Y-m-d H:i:s');
                }

                return [
                    'mode' => (int)$field['mode'],
                    'value' => $field['value'],
                ];
            case \Ess\M2ePro\Model\Walmart\ProductType::FIELD_CUSTOM_ATTRIBUTE:
                if (empty($field['attribute_code'])) {
                    return [];
                }

                return [
                    'mode' => (int)$field['mode'],
                    'attribute_code' => $field['attribute_code'],
                ];
            default:
                throw new \Ess\M2ePro\Model\Exception('Incorrect mode for Product Type attributes settings.');
        }
    }

    public function getDefaultData(): array
    {
        return [
            ProductTypeResource::COLUMN_ID => '',
            ProductTypeResource::COLUMN_TITLE => '',
            ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID => '',
            ProductTypeResource::COLUMN_ATTRIBUTES_SETTINGS => '[]',
        ];
    }
}
