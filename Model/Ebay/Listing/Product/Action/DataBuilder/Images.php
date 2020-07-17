<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

use Ess\M2ePro\Model\Ebay\Template\Description\Source as DescriptionSource;
use Magento\Eav\Model\ResourceModel\Attribute;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\Images
 */
class Images extends AbstractModel
{
    //########################################

    public function getBuilderData()
    {
        $this->searchNotFoundAttributes();

        $links = [];
        $galleryImages = $this->getEbayListingProduct()->getDescriptionTemplateSource()->getGalleryImages();

        foreach ($galleryImages as $image) {
            if (!$image->getUrl()) {
                continue;
            }

            $links[] = $image->getUrl();
        }

        $data = [
            'gallery_type' => $this->getEbayListingProduct()->getEbayDescriptionTemplate()->getGalleryType(),
            'images'       => $links,
            'supersize'    => $this->getEbayListingProduct()
                ->getEbayDescriptionTemplate()
                ->isUseSupersizeImagesEnabled()
        ];

        $this->processNotFoundAttributes('Main Image / Gallery Images');

        $result = [
            'images' => $data,
        ];

        if (!$this->isVariationItem) {
            return $result;
        }

        $result['variation_image'] = $this->getVariationImage();

        return $result;
    }

    //########################################

    protected function getVariationImage()
    {
        $attributeLabels = [];

        if ($this->getMagentoProduct()->isConfigurableType()) {
            $attributeLabels = $this->getConfigurableImagesAttributeLabels();
        }

        if ($this->getMagentoProduct()->isGroupedType()) {
            $attributeLabels = [\Ess\M2ePro\Model\Magento\Product\Variation::GROUPED_PRODUCT_ATTRIBUTE_LABEL];
        }

        if ($this->getMagentoProduct()->isBundleType()) {
            $attributeLabels = $this->getBundleImagesAttributeLabels();
        }

        if (empty($attributeLabels)) {
            return [];
        }

        return $this->getImagesDataByAttributeLabels($attributeLabels);
    }

    protected function getConfigurableImagesAttributeLabels()
    {
        $descriptionTemplate = $this->getEbayListingProduct()->getEbayDescriptionTemplate();

        if (!$descriptionTemplate->isVariationConfigurableImages()) {
            return [];
        }

        $product = $this->getMagentoProduct()->getProduct();

        $attributeCodes = $descriptionTemplate->getDecodedVariationConfigurableImages();
        $attributes = [];

        foreach ($attributeCodes as $attributeCode) {
            /** @var $attribute Attribute */
            $attribute = $product->getResource()->getAttribute($attributeCode);

            if (!$attribute) {
                continue;
            }

            $attribute->setStoreId($product->getStoreId());
            $attributes[] = $attribute;
        }

        if (empty($attributes)) {
            return [];
        }

        $attributeLabels = [];

        /** @var $productTypeInstance \Magento\ConfigurableProduct\Model\Product\Type\Configurable */
        $productTypeInstance = $this->getMagentoProduct()->getTypeInstance();

        foreach ($productTypeInstance->getConfigurableAttributes($product) as $configurableAttribute) {

            /** @var $configurableAttribute \Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute */
            $configurableAttribute->setStoteId($product->getStoreId());

            foreach ($attributes as $attribute) {
                if ((int)$attribute->getAttributeId() == (int)$configurableAttribute->getAttributeId()) {
                    $attributeLabels = [];
                    foreach ($attribute->getStoreLabels() as $storeLabel) {
                        $attributeLabels[] = trim($storeLabel);
                    }

                    $attributeLabels[] = trim($configurableAttribute->getData('label'));
                    $attributeLabels[] = trim($attribute->getFrontendLabel());

                    $attributeLabels = array_filter($attributeLabels);

                    break 2;
                }
            }
        }

        if (empty($attributeLabels)) {
            $this->addNotFoundAttributesMessages(
                $this->getHelper('Module\Translation')->__('Change Images for Attribute'),
                $attributes
            );

            return [];
        }

        return $attributeLabels;
    }

    protected function getBundleImagesAttributeLabels()
    {
        $variations = $this->getMagentoProduct()->getVariationInstance()->getVariationsTypeStandard();
        if (!empty($variations['set'])) {
            return [(string)key($variations['set'])];
        }

        return [];
    }

    protected function getImagesDataByAttributeLabels(array $attributeLabels)
    {
        $images = [];
        $imagesLinks = [];
        $attributeLabel = false;

        foreach ($this->getListingProduct()->getVariations(true) as $variation) {
            /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */

            if ($variation->getChildObject()->isDelete()) {
                continue;
            }

            foreach ($variation->getOptions(true) as $option) {

                /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */

                $optionLabel = trim($option->getAttribute());
                $optionValue = trim($option->getOption());

                $foundAttributeLabel = false;
                foreach ($attributeLabels as $tempLabel) {
                    if (strtolower($tempLabel) == strtolower($optionLabel)) {
                        $foundAttributeLabel = $optionLabel;
                        break;
                    }
                }

                if ($foundAttributeLabel === false) {
                    continue;
                }

                if (!isset($imagesLinks[$optionValue])) {
                    $imagesLinks[$optionValue] = [];
                }

                $attributeLabel = $foundAttributeLabel;
                $optionImages = $this->getEbayListingProduct()->getEbayDescriptionTemplate()
                    ->getSource($option->getMagentoProduct())
                    ->getVariationImages();

                foreach ($optionImages as $image) {
                    if (!$image->getUrl()) {
                        continue;
                    }

                    if (count($imagesLinks[$optionValue]) >= DescriptionSource::VARIATION_IMAGES_COUNT_MAX) {
                        break 2;
                    }

                    if (!isset($images[$image->getHash()])) {
                        $imagesLinks[$optionValue][] = $image->getUrl();
                        $images[$image->getHash()] = $image;
                    }
                }
            }
        }

        if (!$attributeLabel || !$imagesLinks) {
            return [];
        }

        return [
            'specific' => $attributeLabel,
            'images'   => $imagesLinks
        ];
    }

    //########################################
}
