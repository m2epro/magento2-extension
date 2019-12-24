<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing;

use Ess\M2ePro\Model\Amazon\Listing;
use Ess\M2ePro\Model\Magento\Product\Image;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var $listing \Ess\M2ePro\Model\Listing
     */
    private $listing = null;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $this->magentoProduct = $magentoProduct;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct()
    {
        return $this->magentoProduct;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing $listing
     * @return $this
     */
    public function setListing(\Ess\M2ePro\Model\Listing $listing)
    {
        $this->listing = $listing;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        return $this->listing;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing
     */
    public function getAmazonListing()
    {
        return $this->getListing()->getChildObject();
    }

    //########################################

    /**
     * @return string
     */
    public function getSku()
    {
        $result = '';
        $src = $this->getAmazonListing()->getSkuSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_DEFAULT) {
            $result = $this->getMagentoProduct()->getSku();
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_PRODUCT_ID) {
            $result = $this->getMagentoProduct()->getProductId();
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::SKU_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        is_string($result) && $result = trim($result);

        if (!empty($result)) {
            return $this->applySkuModification($result);
        }

        return $result;
    }

    // ---------------------------------------

    protected function applySkuModification($sku)
    {
        if ($this->getAmazonListing()->isSkuModificationModeNone()) {
            return $sku;
        }

        $source = $this->getAmazonListing()->getSkuModificationSource();

        if ($this->getAmazonListing()->isSkuModificationModePrefix()) {
            $sku = $source['value'] . $sku;
        } elseif ($this->getAmazonListing()->isSkuModificationModePostfix()) {
            $sku = $sku . $source['value'];
        } elseif ($this->getAmazonListing()->isSkuModificationModeTemplate()) {
            $sku = str_replace('%value%', $sku, $source['value']);
        }

        return $sku;
    }

    // ---------------------------------------

    /**
     * @return mixed
     */
    public function getSearchGeneralId()
    {
        $result = '';
        $src = $this->getAmazonListing()->getGeneralIdSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::GENERAL_ID_MODE_NOT_SET) {
            $result = null;
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::GENERAL_ID_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
            $result = str_replace('-', '', $result);
        }

        is_string($result) && $result = trim($result);

        return $result;
    }

    /**
     * @return mixed
     */
    public function getSearchWorldwideId()
    {
        $result = '';
        $src = $this->getAmazonListing()->getWorldwideIdSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::WORLDWIDE_ID_MODE_NOT_SET) {
            $result = null;
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::WORLDWIDE_ID_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
            $result = str_replace('-', '', $result);
        }

        is_string($result) && $result = trim($result);

        return $result;
    }

    //########################################

    /**
     * @return int|string
     */
    public function getHandlingTime()
    {
        $result = 0;
        $src = $this->getAmazonListing()->getHandlingTimeSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::HANDLING_TIME_MODE_RECOMMENDED) {
            $result = $src['value'];
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        $result = (int)$result;
        $result < 0 && $result = 0;
        $result > 30 && $result = 30;

        return $result;
    }

    /**
     * @return string
     */
    public function getRestockDate()
    {
        $result = '';
        $src = $this->getAmazonListing()->getRestockDateSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::RESTOCK_DATE_MODE_CUSTOM_VALUE) {
            $result = $src['value'];
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return trim($result);
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getCondition()
    {
        $result = '';
        $src = $this->getAmazonListing()->getConditionSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_DEFAULT) {
            $result = $src['value'];
        }

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::CONDITION_MODE_CUSTOM_ATTRIBUTE) {
            $result = $this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return trim($result);
    }

    /**
     * @return string
     */
    public function getConditionNote()
    {
        $result = '';
        $src = $this->getAmazonListing()->getConditionNoteSource();

        if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Listing::CONDITION_NOTE_MODE_CUSTOM_VALUE) {
            $renderer = $this->getHelper('Module_Renderer_Description');
            $result = $renderer->parseTemplate($src['value'], $this->getMagentoProduct());
        }

        return trim($result);
    }

    // ---------------------------------------

    /**
     * @return Image|null
     */
    public function getMainImage()
    {
        $image = null;

        if ($this->getAmazonListing()->isImageMainModeProduct()) {
            $image = $this->getMagentoProduct()->getImage('image');
        }

        if ($this->getAmazonListing()->isImageMainModeAttribute()) {
            $src = $this->getAmazonListing()->getImageMainSource();
            $image = $this->getMagentoProduct()->getImage($src['attribute']);
        }

        return $image;
    }

    /**
     * @return Image[]
     */
    public function getGalleryImages()
    {
        if ($this->getAmazonListing()->isImageMainModeNone()) {
            return [];
        }

        $allowedConditionValues = [
            \Ess\M2ePro\Model\Amazon\Listing::CONDITION_USED_LIKE_NEW,
            \Ess\M2ePro\Model\Amazon\Listing::CONDITION_USED_VERY_GOOD,
            \Ess\M2ePro\Model\Amazon\Listing::CONDITION_USED_GOOD,
            \Ess\M2ePro\Model\Amazon\Listing::CONDITION_USED_ACCEPTABLE,
            \Ess\M2ePro\Model\Amazon\Listing::CONDITION_COLLECTIBLE_LIKE_NEW,
            \Ess\M2ePro\Model\Amazon\Listing::CONDITION_COLLECTIBLE_VERY_GOOD,
            \Ess\M2ePro\Model\Amazon\Listing::CONDITION_COLLECTIBLE_GOOD,
            \Ess\M2ePro\Model\Amazon\Listing::CONDITION_COLLECTIBLE_ACCEPTABLE
        ];

        $conditionData = $this->getAmazonListing()->getConditionSource();

        if ($this->getAmazonListing()->isConditionDefaultMode() &&
            !in_array($conditionData['value'], $allowedConditionValues)) {
            return [];
        }

        if ($this->getAmazonListing()->isConditionAttributeMode()) {
            $tempConditionValue = $this->getMagentoProduct()->getAttributeValue($conditionData['attribute']);

            if (!in_array($tempConditionValue, $allowedConditionValues)) {
                return [];
            }
        }

        if (!$mainImage = $this->getMainImage()) {
            return [];
        }

        if ($this->getAmazonListing()->isGalleryImagesModeNone()) {
            return [$mainImage];
        }

        $galleryImages = [];
        $gallerySource = $this->getAmazonListing()->getGalleryImagesSource();
        $limitGalleryImages = Listing::GALLERY_IMAGES_COUNT_MAX;

        if ($this->getAmazonListing()->isGalleryImagesModeProduct()) {
            $limitGalleryImages = (int)$gallerySource['limit'];
            $galleryImagesTemp = $this->getMagentoProduct()->getGalleryImages($limitGalleryImages + 1);

            foreach ($galleryImagesTemp as $image) {
                if (array_key_exists($image->getHash(), $galleryImages)) {
                    continue;
                }

                $galleryImages[$image->getHash()] = $image;
            }
        }

        if ($this->getAmazonListing()->isGalleryImagesModeAttribute()) {
            $limitGalleryImages = Listing::GALLERY_IMAGES_COUNT_MAX;

            $galleryImagesTemp = $this->getMagentoProduct()->getAttributeValue($gallerySource['attribute']);
            $galleryImagesTemp = (array)explode(',', $galleryImagesTemp);

            foreach ($galleryImagesTemp as $tempImageLink) {
                $tempImageLink = trim($tempImageLink);
                if (empty($tempImageLink)) {
                    continue;
                }

                /** @var Image $image */
                $image = $this->modelFactory->getObject('Magento_Product_Image');
                $image->setUrl($tempImageLink);
                $image->setStoreId($this->getMagentoProduct()->getStoreId());

                if (array_key_exists($image->getHash(), $galleryImages)) {
                    continue;
                }

                $galleryImages[$image->getHash()] = $image;
            }
        }

        unset($galleryImages[$mainImage->getHash()]);

        if (count($galleryImages) <= 0) {
            return [$mainImage];
        }

        $galleryImages = array_slice($galleryImages, 0, $limitGalleryImages);
        array_unshift($galleryImages, $mainImage);

        return $galleryImages;
    }

    // ---------------------------------------

    /**
     * @return mixed
     */
    public function getGiftWrap()
    {
        $result = null;
        $src = $this->getAmazonListing()->getGiftWrapSource();

        if ($this->getAmazonListing()->isGiftWrapModeYes()) {
            $result = true;
        }

        if ($this->getAmazonListing()->isGiftWrapModeNo()) {
            $result = false;
        }

        if ($this->getAmazonListing()->isGiftWrapModeAttribute()) {
            $attributeValue = $this->getMagentoProduct()->getAttributeValue($src['attribute']);

            if ($attributeValue == $this->getHelper('Module\Translation')->__('Yes')) {
                $result = true;
            }

            if ($attributeValue == $this->getHelper('Module\Translation')->__('No')) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * @return null|bool
     */
    public function getGiftMessage()
    {
        $result = null;
        $src = $this->getAmazonListing()->getGiftMessageSource();

        if ($this->getAmazonListing()->isGiftMessageModeYes()) {
            $result = true;
        }

        if ($this->getAmazonListing()->isGiftMessageModeNo()) {
            $result = false;
        }

        if ($this->getAmazonListing()->isGiftMessageModeAttribute()) {
            $attributeValue = $this->getMagentoProduct()->getAttributeValue($src['attribute']);

            if ($attributeValue == $this->getHelper('Module\Translation')->__('Yes')) {
                $result = true;
            }

            if ($attributeValue == $this->getHelper('Module\Translation')->__('No')) {
                $result = false;
            }
        }

        return $result;
    }

    //########################################
}
