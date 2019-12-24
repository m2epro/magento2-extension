<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ss-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder;

use Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\Details
 */
class Details extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\AbstractModel
{
    //########################################

    public function getRequestData()
    {
        $sellingFormatTemplateSource = $this->getWalmartListingProduct()->getSellingFormatTemplateSource();

        $data = [
            'product_data_nick'     => $this->getWalmartListingProduct()->getCategoryTemplate()->getProductDataNick(),
            'product_data'          => $this->getProductData(),
            'product_ids_data'      => $this->getProductIdsData(),
            'description_data'      => $this->getDescriptionData(),
            'shipping_weight'       => $sellingFormatTemplateSource->getItemWeight(),
            'tax_code'              => $sellingFormatTemplateSource->getProductTaxCode(),
            'additional_attributes' => $sellingFormatTemplateSource->getAttributes(),
        ];

        if ($this->getWalmartListingProduct()->getWpid()) {
            $data['wpid'] = $this->getWalmartListingProduct()->getWpid();
        }

        $startDate = $this->getWalmartListingProduct()->getSellingFormatTemplateSource()->getStartDate();
        if (!empty($startDate)) {
            $data['start_date'] = $startDate;
        } else {
            $data['start_date'] = $this->getHelper('Data')->getCurrentGmtDate(false, 'Y-m-d');
        }

        $endDate = $this->getWalmartListingProduct()->getSellingFormatTemplateSource()->getEndDate();
        if (!empty($endDate)) {
            $data['end_date'] = $endDate;
        } else {
            $data['end_date'] = '9999-01-01';
        }

        $mapPrice = $this->getWalmartListingProduct()->getMapPrice();
        if (!empty($mapPrice)) {
            $data['map_price'] = $mapPrice;
        }

        $mustShipAlone = $this->getWalmartListingProduct()->getSellingFormatTemplateSource()->getMustShipAlone();
        if ($mustShipAlone !== null) {
            $data['is_must_ship_alone'] = $mustShipAlone;
        }

        $shipsInOriginalPackaging = $sellingFormatTemplateSource->getShipsInOriginalPackaging();
        if ($shipsInOriginalPackaging !== null) {
            $data['is_ship_in_original_packaging'] = $shipsInOriginalPackaging;
        }

        $shippingOverrides = $this->getShippingOverrides();
        if (!empty($shippingOverrides)) {
            $data['shipping_overrides'] = $shippingOverrides;
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
                'group_id'   => $variationGroupId,
                'attributes' => $typeModel->getRealChannelOptions(),
            ];
        }

        return $data;
    }

    //########################################

    private function getProductData()
    {
        $data = [];

        $this->searchNotFoundAttributes();

        foreach ($this->getWalmartListingProduct()->getCategoryTemplate()->getSpecifics(true) as $specific) {
            $source = $specific->getSource($this->getWalmartListingProduct()->getActualMagentoProduct());

            if (!$specific->isRequired() && !$specific->isModeNone() && !$source->getValue()) {
                continue;
            }

            $data = array_replace_recursive(
                $data,
                $this->getHelper('Data')->jsonDecode($source->getPath())
            );
        }

        $this->processNotFoundAttributes('Product Specifics');

        return $data;
    }

    // ---------------------------------------

    private function getProductIdsData()
    {
        $data = [];

        $idsTypes = ['gtin', 'upc', 'ean', 'isbn'];

        foreach ($idsTypes as $idType) {
            if (!isset($this->cachedData[$idType])) {
                continue;
            }

            $data[] = [
                'type' => strtoupper($idType),
                'id'   => $this->cachedData[$idType]
            ];
        }

        return $data;
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
        $data['keywords'] = $source->getKeywords();
        $this->processNotFoundAttributes('Keywords');

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

        $this->searchNotFoundAttributes();
        $data['additional_attributes'] = $source->getAttributes();
        $this->processNotFoundAttributes('Attributes');

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
            'url'          => $url,
            'by_attribute' => $swatchAttribute,
        ];

        return [$swatchImageData];
    }

    // ---------------------------------------

    private function getShippingOverrides()
    {
        $result = [];

        $shippingOverrides = $this->getWalmartListingProduct()->getWalmartSellingFormatTemplate()
            ->getShippingOverrides(true);

        if (empty($shippingOverrides)) {
            return $result;
        }

        foreach ($shippingOverrides as $shippingOverride) {
            $source = $shippingOverride->getSource(
                $this->getWalmartListingProduct()->getActualMagentoProduct()
            );

            $result[] = [
                'ship_method'         => $shippingOverride->getMethod(),
                'ship_region'         => $shippingOverride->getRegion(),
                'ship_price'          => $source->getCost(),
                'is_shipping_allowed' => (bool)$shippingOverride->getIsShippingAllowed(),
            ];
        }

        return $result;
    }

    //########################################
}
