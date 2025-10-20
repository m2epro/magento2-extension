<?php

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder;

use Ess\M2ePro\Helper\Data\Product\Identifier;
use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

class Details extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\AbstractModel
{
    private \Ess\M2ePro\Model\Walmart\ProductType\AttributeSetting\Provider $attributeSettingProvider;
    private \Ess\M2ePro\Model\Walmart\Listing\Product\ProviderFactory $listingProviderFactory;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ProductType\AttributeSetting\Provider $attributeSettingProvider,
        \Ess\M2ePro\Model\Walmart\Listing\Product\ProviderFactory $listingProviderFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->attributeSettingProvider = $attributeSettingProvider;
        $this->listingProviderFactory = $listingProviderFactory;
    }

    public function getBuilderData()
    {
        $walmartListingProduct = $this->getWalmartListingProduct();
        $sellingFormatTemplateSource = $walmartListingProduct->getSellingFormatTemplateSource();
        $walmartListingProductProvider = $this->listingProviderFactory->create(
            $walmartListingProduct
        );

        $data = [
            'product_id_data' => $this->getProductIdData(),
            'description_data' => $this->getDescriptionData(),
            'shipping_weight' => $sellingFormatTemplateSource->getItemWeight(),
        ];

        if (
            $this->getWalmartMarketplace()
                 ->isSupportedProductType()
        ) {
            if ($walmartListingProduct->isExistsProductType()) {
                $data['product_type_nick'] = $walmartListingProduct->getProductType()
                                                                   ->getNick();
                $data['attributes'] = $this->getAttributes(
                    $walmartListingProduct->getProductType(),
                    $walmartListingProduct->getActualMagentoProduct()
                );
            } else {
                /** match with existing chanel item */
                $data['matching_mode'] = true;
            }
        }

        $condition = $this->retrieveCondition($walmartListingProductProvider);
        if ($condition !== null) {
            $data['condition'] = $condition;
        }

        if ($this->getWalmartListingProduct()->getWpid()) {
            $data['wpid'] = $this->getWalmartListingProduct()->getWpid();
        }

        $startDate = $this->getWalmartListingProduct()->getSellingFormatTemplateSource()->getStartDate();
        if (!empty($startDate)) {
            $data['start_date'] = $startDate;
        } else {
            $data['start_date'] = '1970-01-01 00:00:00';
        }

        $endDate = $this->getWalmartListingProduct()->getSellingFormatTemplateSource()->getEndDate();
        if (!empty($endDate)) {
            $data['end_date'] = $endDate;
        } else {
            $data['end_date'] = '9999-01-01 00:00:00';
        }

        $mustShipAlone = $this->getWalmartListingProduct()->getSellingFormatTemplateSource()->getMustShipAlone();
        if ($mustShipAlone !== null) {
            $data['is_must_ship_alone'] = $mustShipAlone;
        }

        $shipsInOriginalPackaging = $sellingFormatTemplateSource->getShipsInOriginalPackaging();
        if ($shipsInOriginalPackaging !== null) {
            $data['is_ship_in_original_packaging'] = $shipsInOriginalPackaging;
        }

        if ($this->getWalmartListingProduct()->getVariationManager()->isRelationChildType()) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $typeModel */
            $typeModel = $this->getWalmartListingProduct()->getVariationManager()->getTypeModel();

            /** @var \Ess\M2ePro\Model\Listing\Product $parentListingProduct */
            $parentListingProduct = $typeModel->getParentListingProduct();

            /** @var ParentRelation $parentTypeModel */
            $parentTypeModel = $parentListingProduct->getChildObject()->getVariationManager()->getTypeModel();

            if ($parentTypeModel->hasChannelGroupId()) {
                $variationGroupId = $parentTypeModel->getChannelGroupId();
            } else {
                $variationGroupId = $this->getHelper('Data')->generateUniqueHash($parentListingProduct->getId());
                $parentTypeModel->setChannelGroupId($variationGroupId, true);
            }

            $data['variation_data'] = [
                'group_id' => $variationGroupId,
                'attributes' => $typeModel->getRealChannelOptions(),
            ];
        }

        return $data;
    }

    private function retrieveCondition(\Ess\M2ePro\Model\Walmart\Listing\Product\Provider $provider): ?string
    {
        $this->searchNotFoundAttributes();
        $condition = $provider->retrieveCondition();

        if ($condition === null) {
            return null;
        }

        if ($condition->isNotFoundMagentoAttribute) {
            $this->processNotFoundAttributes('Condition');

            return null;
        }

        return $condition->value;
    }

    /**
     * @return list<string, string[]|string>
     */
    private function getAttributes(
        \Ess\M2ePro\Model\Walmart\ProductType $productType,
        \Ess\M2ePro\Model\Magento\Product $product
    ): array {
        $attributes = $this->attributeSettingProvider->getAttributes($productType, $product);

        $resultData = [];
        foreach ($attributes as $attribute) {
            $value = $attribute->getValues();
            if (count($value) === 1) {
                $value = reset($value);
            }
            $resultData[$attribute->getName()] = $value;
        }

        return $resultData;
    }

    // ---------------------------------------

    private function getProductIdData()
    {
        if (!isset($this->cachedData['identifier'])) {
            $this->cachedData['identifier'] = $this->getIdentifierFromProduct();
        }

        return $this->cachedData['identifier'];
    }

    private function getIdentifierFromProduct(): array
    {
        $walmartListingProduct = $this->getListingProduct()->getChildObject();

        if ($identifier = $walmartListingProduct->getGtin()) {
            return [
                'type' => Identifier::GTIN,
                'id' => $identifier
            ];
        }

        if ($identifier = $walmartListingProduct->getUpc()) {
            return [
                'type' => Identifier::UPC,
                'id' => $identifier
            ];
        }

        if ($identifier = $walmartListingProduct->getEan()) {
            return [
                'type' => Identifier::EAN,
                'id' => $identifier
            ];
        }

        if ($identifier = $walmartListingProduct->getIsbn()) {
            return [
                'type' => Identifier::ISBN,
                'id' => $identifier
            ];
        }

        return [];
    }

    // ---------------------------------------

    private function getDescriptionData()
    {
        $source = $this->getWalmartListingProduct()->getDescriptionTemplateSource();

        $data = [];

        $this->searchNotFoundAttributes();
        $data['title'] = $source->getTitle();
        $this->processNotFoundAttributes('Title');

        $this->searchNotFoundAttributes();
        $data['brand'] = $source->getBrand();
        $this->processNotFoundAttributes('Brand');

        $this->searchNotFoundAttributes();
        $data['manufacturer'] = $source->getManufacturer();
        $this->processNotFoundAttributes('Manufacturer');

        $this->searchNotFoundAttributes();
        $data['manufacturer_part_number'] = $source->getManufacturerPartNumber();
        $this->processNotFoundAttributes('Manufacturer Part Number');

        $this->searchNotFoundAttributes();
        $data['count_per_pack'] = $source->getCountPerPack();
        $this->processNotFoundAttributes('Count Per Pack');

        $this->searchNotFoundAttributes();
        $data['multipack_quantity'] = $source->getMultipackQuantity();
        $this->processNotFoundAttributes('Multipack Quantity');

        $this->searchNotFoundAttributes();
        $data['count'] = $source->getTotalCount();
        $this->processNotFoundAttributes('Total Count');

        $this->searchNotFoundAttributes();
        $data['model_number'] = $source->getModelNumber();
        $this->processNotFoundAttributes('Model Number');

        $this->searchNotFoundAttributes();
        $data['short_description'] = $source->getDescription();
        $this->processNotFoundAttributes('Short Description');

        $this->searchNotFoundAttributes();
        $data['key_features'] = $source->getKeyFeatures();
        $this->processNotFoundAttributes('Key Features');

        $this->searchNotFoundAttributes();
        $data['features'] = $source->getOtherFeatures();
        $this->processNotFoundAttributes('Other Features');

        $this->searchNotFoundAttributes();
        $data['msrp'] = $source->getMsrpRrp();
        $this->processNotFoundAttributes('MSRP / RRP');

        $this->searchNotFoundAttributes();
        $data['main_image_url'] = $this->getMainImageUrl();
        $this->processNotFoundAttributes('Other Features');

        $this->searchNotFoundAttributes();
        $data['product_secondary_image_url'] = $this->getProductSecondaryImageUrls();
        $this->processNotFoundAttributes('Gallery Images');

        if ($this->getVariationManager()->isRelationChildType()) {
            $data['swatch_images'] = $this->getSwatchImages();
        }

        return $data;
    }

    private function getMainImageUrl()
    {
        $mainImage = $this->getWalmartListingProduct()->getDescriptionTemplateSource()->getMainImage();

        if ($mainImage === null) {
            return '';
        }

        $walmartConfigurationHelper = $this->getHelper('Component_Walmart_Configuration');

        if ($walmartConfigurationHelper->isOptionImagesURLHTTPSMode()) {
            return str_replace('http://', 'https://', $mainImage->getUrl());
        }

        if ($walmartConfigurationHelper->isOptionImagesURLHTTPMode()) {
            return str_replace('https://', 'http://', $mainImage->getUrl());
        }

        return $mainImage->getUrl();
    }

    private function getProductSecondaryImageUrls()
    {
        $urls = [];

        $walmartConfigurationHelper = $this->getHelper('Component_Walmart_Configuration');
        foreach ($this->getWalmartListingProduct()->getDescriptionTemplateSource()->getGalleryImages() as $image) {
            if (!$image->getUrl()) {
                continue;
            }

            if ($walmartConfigurationHelper->isOptionImagesURLHTTPSMode()) {
                $urls[] = str_replace('http://', 'https://', $image->getUrl());
                continue;
            }

            if ($walmartConfigurationHelper->isOptionImagesURLHTTPMode()) {
                $urls[] = str_replace('https://', 'http://', $image->getUrl());
                continue;
            }

            $urls[] = $image->getUrl();
        }

        return $urls;
    }

    private function getSwatchImages()
    {
        if (!$this->getVariationManager()->isRelationChildType()) {
            return [];
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\Child $childTypeModel */
        $childTypeModel = $this->getVariationManager()->getTypeModel();

        $swatchAttribute = $childTypeModel->getParentTypeModel()->getSwatchImagesAttribute();
        if (empty($swatchAttribute)) {
            return [];
        }

        $image = $this->getWalmartListingProduct()->getDescriptionTemplateSource()->getVariationDifferenceImage();
        if ($image === null) {
            return [];
        }

        $walmartConfigurationHelper = $this->getHelper('Component_Walmart_Configuration');
        $url = $image->getUrl();

        if ($walmartConfigurationHelper->isOptionImagesURLHTTPSMode()) {
            $url = str_replace('http://', 'https://', $url);
        }

        if ($walmartConfigurationHelper->isOptionImagesURLHTTPMode()) {
            $url = str_replace('https://', 'http://', $url);
        }

        $swatchImageData = [
            'url' => $url,
            'by_attribute' => $swatchAttribute,
        ];

        return [$swatchImageData];
    }

    // ---------------------------------------
}
