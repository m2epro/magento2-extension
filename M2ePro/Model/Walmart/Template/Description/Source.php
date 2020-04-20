<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Description;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\Description\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    const GALLERY_IMAGES_COUNT_MAX = 8;

    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var $descriptionTemplateModel \Ess\M2ePro\Model\Template\Description
     */
    private $descriptionTemplateModel = null;

    private $emailTemplateFilter;

    //########################################

    public function __construct(
        \Magento\Email\Model\Template\Filter $emailTemplateFilter,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->emailTemplateFilter = $emailTemplateFilter;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

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
     * @param \Ess\M2ePro\Model\Template\Description $instance
     * @return $this
     */
    public function setDescriptionTemplate(\Ess\M2ePro\Model\Template\Description $instance)
    {
        $this->descriptionTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        return $this->descriptionTemplateModel;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Description
     */
    public function getWalmartDescriptionTemplate()
    {
        return $this->descriptionTemplateModel->getChildObject();
    }

    //########################################

    /**
     * @return string
     */
    public function getTitle()
    {
        $src = $this->getWalmartDescriptionTemplate()->getTitleSource();

        switch ($src['mode']) {
            case \Ess\M2ePro\Model\Walmart\Template\Description::TITLE_MODE_PRODUCT:
                $title = $this->getMagentoProduct()->getName();
                break;

            case \Ess\M2ePro\Model\Walmart\Template\Description::TITLE_MODE_CUSTOM:
                $title = $this->getHelper('Module_Renderer_Description')->parseTemplate(
                    $src['template'],
                    $this->getMagentoProduct()
                );
                break;

            default:
                $title = $this->getMagentoProduct()->getName();
                break;
        }

        return $title;
    }

    /**
     * @return null|string
     */
    public function getBrand()
    {
        $src = $this->getWalmartDescriptionTemplate()->getBrandSource();

        if ($this->getWalmartDescriptionTemplate()->isBrandModeCustomValue()) {
            return trim($src['custom_value']);
        }

        return trim($this->getMagentoProduct()->getAttributeValue($src['custom_attribute']));
    }

    /**
     * @return int|null|string
     */
    public function getCountPerPack()
    {
        $result = '';
        $src = $this->getWalmartDescriptionTemplate()->getCountPerPackSource();

        if ($this->getWalmartDescriptionTemplate()->isCountPerPackModeNone()) {
            $result = null;
        }

        if ($this->getWalmartDescriptionTemplate()->isCountPerPackModeCustomValue()) {
            $result = (int)$src['value'];
        }

        if ($this->getWalmartDescriptionTemplate()->isCountPerPackModeCustomAttribute()) {
            $result = (int)$this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $result;
    }

    /**
     * @return int|null|string
     */
    public function getMultipackQuantity()
    {
        $result = '';
        $src = $this->getWalmartDescriptionTemplate()->getMultipackQuantitySource();

        if ($this->getWalmartDescriptionTemplate()->isMultipackQuantityModeNone()) {
            $result = null;
        }

        if ($this->getWalmartDescriptionTemplate()->isMultipackQuantityModeCustomValue()) {
            $result = (int)$src['value'];
        }

        if ($this->getWalmartDescriptionTemplate()->isMultipackQuantityModeCustomAttribute()) {
            $result = (int)$this->getMagentoProduct()->getAttributeValue($src['attribute']);
        }

        return $result;
    }

    /**
     * @return int|null|string
     */
    public function getTotalCount()
    {
        $result = 0;
        $src = $this->getWalmartDescriptionTemplate()->getTotalCountSource();

        if ($this->getWalmartDescriptionTemplate()->isTotalCountModeNone()) {
            $result = null;
        }

        if ($this->getWalmartDescriptionTemplate()->isTotalCountModeCustomValue()) {
            $result = (int)$src['custom_value'];
        }

        if ($this->getWalmartDescriptionTemplate()->isTotalCountModeCustomAttribute()) {
            $result = (int)$this->getMagentoProduct()->getAttributeValue($src['custom_attribute']);
        }

        return $result;
    }

    /**
     * @return int|null|string
     */
    public function getModelNumber()
    {
        $result = '';
        $src = $this->getWalmartDescriptionTemplate()->getModelNumberSource();

        if ($this->getWalmartDescriptionTemplate()->isModelNumberModeNone()) {
            $result = null;
        }

        if ($this->getWalmartDescriptionTemplate()->isModelNumberModeCustomValue()) {
            $result = (int)$src['custom_value'];
        }

        if ($this->getWalmartDescriptionTemplate()->isModelNumberModeCustomAttribute()) {
            $result = (int)$this->getMagentoProduct()->getAttributeValue($src['custom_attribute']);
        }

        return $result;
    }

    /**
     * @return float|null
     */
    public function getMsrpRrp($storeForConvertingAttributeTypePrice = null)
    {
        $result = '';

        if ($this->getWalmartDescriptionTemplate()->isMsrpRrpModeNone()) {
            return null;
        }

        if ($this->getWalmartDescriptionTemplate()->isMsrpRrpModeCustomAttribute()) {
            $src = $this->getWalmartDescriptionTemplate()->getMsrpRrpSource();
            $result = $this->getMagentoProductAttributeValue(
                $src['custom_attribute']
            );
        }

        is_string($result) && $result = str_replace(',', '.', $result);

        return round((float)$result, 2);
    }

    /**
     * @return mixed|string
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getDescription()
    {
        $src = $this->getWalmartDescriptionTemplate()->getDescriptionSource();

        switch ($src['mode']) {
            case \Ess\M2ePro\Model\Walmart\Template\Description::DESCRIPTION_MODE_PRODUCT:
                $description = $this->getMagentoProduct()->getProduct()->getDescription();
                $description = $this->emailTemplateFilter->filter($description);
                break;

            case \Ess\M2ePro\Model\Walmart\Template\Description::DESCRIPTION_MODE_SHORT:
                $description = $this->getMagentoProduct()->getProduct()->getShortDescription();
                $description = $this->emailTemplateFilter->filter($description);
                break;

            case \Ess\M2ePro\Model\Walmart\Template\Description::DESCRIPTION_MODE_CUSTOM:
                $description = $this->getHelper('Module_Renderer_Description')->parseTemplate(
                    $src['template'],
                    $this->getMagentoProduct()
                );
                break;

            default:
                $description = '';
                break;
        }

        $allowedTags = ['<p>', '<br>', '<ul>', '<li>', '<b>'];

        $description = str_replace(['<![CDATA[', ']]>'], '', $description);
        $description = strip_tags($description, implode($allowedTags));

        return $description;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getKeyFeatures()
    {
        if ($this->getWalmartDescriptionTemplate()->isKeyFeaturesModeNone()) {
            return [];
        }

        $result = [];
        $src = $this->getWalmartDescriptionTemplate()->getKeyFeaturesSource();

        foreach ($src['template'] as $value) {
            $parsedValue = $this->getHelper('Module_Renderer_Description')->parseTemplate(
                $value,
                $this->getMagentoProduct()
            );

            if (empty($parsedValue)) {
                continue;
            }

            $result[] = $parsedValue;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getOtherFeatures()
    {
        if ($this->getWalmartDescriptionTemplate()->isOtherFeaturesModeNone()) {
            return [];
        }

        $result = [];
        $src = $this->getWalmartDescriptionTemplate()->getOtherFeaturesSource();

        foreach ($src['template'] as $value) {
            $parsedValue = $this->getHelper('Module_Renderer_Description')->parseTemplate(
                $value,
                $this->getMagentoProduct()
            );

            if (empty($parsedValue)) {
                continue;
            }

            $result[] = $parsedValue;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        if ($this->getWalmartDescriptionTemplate()->isAttributesModeNone()) {
            return [];
        }

        $result = [];
        $src = $this->getWalmartDescriptionTemplate()->getAttributesSource();

        foreach ($src['template'] as $value) {
            if (empty($value)) {
                continue;
            }

            $result[$value['name']] = $this->getHelper('Module_Renderer_Description')->parseTemplate(
                $value['value'],
                $this->getMagentoProduct()
            );
        }

        return $result;
    }

    // ---------------------------------------

    /**
     * @return null|string
     */
    public function getKeywords()
    {
        if ($this->getWalmartDescriptionTemplate()->isKeywordsModeNone()) {
            return null;
        }

        $src = $this->getWalmartDescriptionTemplate()->getKeywordsSource();

        if ($this->getWalmartDescriptionTemplate()->isKeywordsModeCustomValue()) {
            return trim($src['custom_value']);
        }

        return trim($this->getMagentoProduct()->getAttributeValue($src['custom_attribute']));
    }

    // ---------------------------------------

    /**
     * @return null|string
     */
    public function getManufacturer()
    {
        $src = $this->getWalmartDescriptionTemplate()->getManufacturerSource();

        if ($this->getWalmartDescriptionTemplate()->isManufacturerModeNone()) {
            return null;
        }

        if ($this->getWalmartDescriptionTemplate()->isManufacturerModeCustomValue()) {
            return trim($src['custom_value']);
        }

        return trim($this->getMagentoProduct()->getAttributeValue($src['custom_attribute']));
    }

    /**
     * @return null|string
     */
    public function getManufacturerPartNumber()
    {
        if ($this->getWalmartDescriptionTemplate()->isManufacturerPartNumberModeNone()) {
            return null;
        }

        $src = $this->getWalmartDescriptionTemplate()->getManufacturerPartNumberSource();

        if ($this->getWalmartDescriptionTemplate()->isManufacturerPartNumberModeCustomValue()) {
            return trim($src['custom_value']);
        }

        return trim($this->getMagentoProduct()->getAttributeValue($src['custom_attribute']));
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Image|null
     */
    public function getMainImage()
    {
        $image = null;

        if ($this->getWalmartDescriptionTemplate()->isImageMainModeProduct()) {
            $image = $this->getMagentoProduct()->getImage('image');
        }

        if ($this->getWalmartDescriptionTemplate()->isImageMainModeAttribute()) {
            $src = $this->getWalmartDescriptionTemplate()->getImageMainSource();
            $image = $this->getMagentoProduct()->getImage($src['attribute']);
        }

        return $image;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Image[]
     */
    public function getGalleryImages()
    {
        if ($this->getWalmartDescriptionTemplate()->isImageMainModeNone()) {
            return [];
        }

        if (!$mainImage = $this->getMainImage()) {
            return [];
        }

        if ($this->getWalmartDescriptionTemplate()->isGalleryImagesModeNone()) {
            return [];
        }

        $galleryImages = [];
        $gallerySource = $this->getWalmartDescriptionTemplate()->getGalleryImagesSource();
        $limitGalleryImages = self::GALLERY_IMAGES_COUNT_MAX;

        if ($this->getWalmartDescriptionTemplate()->isGalleryImagesModeProduct()) {
            $limitGalleryImages = (int)$gallerySource['limit'];
            $galleryImagesTemp = $this->getMagentoProduct()->getGalleryImages($limitGalleryImages + 1);

            foreach ($galleryImagesTemp as $image) {
                if (array_key_exists($image->getHash(), $galleryImages)) {
                    continue;
                }

                $galleryImages[$image->getHash()] = $image;
            }
        }

        if ($this->getWalmartDescriptionTemplate()->isGalleryImagesModeAttribute()) {
            $limitGalleryImages = self::GALLERY_IMAGES_COUNT_MAX;

            $galleryImagesTemp = $this->getMagentoProduct()->getAttributeValue($gallerySource['attribute']);
            $galleryImagesTemp = (array)explode(',', $galleryImagesTemp);

            foreach ($galleryImagesTemp as $tempImageLink) {
                $tempImageLink = trim($tempImageLink);
                if (empty($tempImageLink)) {
                    continue;
                }

                /** @var \Ess\M2ePro\Model\Magento\Product\Image $image */
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

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Image
     */
    public function getVariationDifferenceImage()
    {
        if ($this->getWalmartDescriptionTemplate()->isImageVariationDifferenceModeNone()) {
            return null;
        }

        $image = null;

        if ($this->getWalmartDescriptionTemplate()->isImageVariationDifferenceModeProduct()) {
            $image = $this->getMagentoProduct()->getImage('image');
        }

        if ($this->getWalmartDescriptionTemplate()->isImageVariationDifferenceModeAttribute()) {
            $src = $this->getWalmartDescriptionTemplate()->getImageVariationDifferenceSource();
            $image = $this->getMagentoProduct()->getImage($src['attribute']);
        }

        if (!$image) {
            return null;
        }

        return $image;
    }

    protected function getMagentoProductAttributeValue($attributeCode)
    {
        return $this->getMagentoProduct()->getAttributeValue($attributeCode);
    }

    //########################################
}
