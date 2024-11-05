<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template;

use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description as DescriptionResource;

/**
 * @method \Ess\M2ePro\Model\Template\Description getParentObject()
 * @method DescriptionResource getResource()
 */
class Description extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    public const TITLE_MODE_PRODUCT = 0;
    public const TITLE_MODE_CUSTOM = 1;

    public const SUBTITLE_MODE_NONE = 0;
    public const SUBTITLE_MODE_CUSTOM = 1;

    public const DESCRIPTION_MODE_PRODUCT = 0;
    public const DESCRIPTION_MODE_SHORT = 1;
    public const DESCRIPTION_MODE_CUSTOM = 2;

    public const CONDITION_MODE_EBAY = 0;
    public const CONDITION_MODE_ATTRIBUTE = 1;
    public const CONDITION_MODE_NONE = 2;

    public const CONDITION_EBAY_NEW = 1000;
    public const CONDITION_EBAY_NEW_OTHER = 1500;
    public const CONDITION_EBAY_NEW_WITH_DEFECT = 1750;
    public const CONDITION_EBAY_CERTIFIED_REFURBISHED = 2000;
    public const CONDITION_EBAY_EXCELLENT_REFURBISHED = 2010;
    public const CONDITION_EBAY_VERY_GOOD_REFURBISHED = 2020;
    public const CONDITION_EBAY_GOOD_REFURBISHED = 2030;
    public const CONDITION_EBAY_SELLER_REFURBISHED = 2500;
    public const CONDITION_EBAY_LIKE_NEW = 2750;
    public const CONDITION_EBAY_USED = 3000;
    public const CONDITION_EBAY_VERY_GOOD = 4000;
    public const CONDITION_EBAY_GOOD = 5000;
    public const CONDITION_EBAY_ACCEPTABLE = 6000;
    public const CONDITION_EBAY_NOT_WORKING = 7000;

    public const CONDITION_NOTE_MODE_NONE = 0;
    public const CONDITION_NOTE_MODE_CUSTOM = 1;

    public const EDITOR_TYPE_SIMPLE = 0;
    public const EDITOR_TYPE_TINYMCE = 1;

    public const CUT_LONG_TITLE_DISABLED = 0;
    public const CUT_LONG_TITLE_ENABLED = 1;

    public const PRODUCT_DETAILS_MODE_NONE = 0;
    public const PRODUCT_DETAILS_MODE_DOES_NOT_APPLY = 1;
    public const PRODUCT_DETAILS_MODE_ATTRIBUTE = 2;

    public const GALLERY_TYPE_EMPTY = 4;
    public const GALLERY_TYPE_NO = 0;
    public const GALLERY_TYPE_PICTURE = 1;
    public const GALLERY_TYPE_PLUS = 2;
    public const GALLERY_TYPE_FEATURED = 3;

    public const IMAGE_MAIN_MODE_NONE = 0;
    public const IMAGE_MAIN_MODE_PRODUCT = 1;
    public const IMAGE_MAIN_MODE_ATTRIBUTE = 2;

    public const VIDEO_MODE_NONE = 0;
    public const VIDEO_MODE_ATTRIBUTE = 2;

    public const GALLERY_IMAGES_MODE_NONE = 0;
    public const GALLERY_IMAGES_MODE_PRODUCT = 1;
    public const GALLERY_IMAGES_MODE_ATTRIBUTE = 2;

    public const VARIATION_IMAGES_MODE_NONE = 0;
    public const VARIATION_IMAGES_MODE_PRODUCT = 1;
    public const VARIATION_IMAGES_MODE_ATTRIBUTE = 2;

    public const USE_SUPERSIZE_IMAGES_NO = 0;
    public const USE_SUPERSIZE_IMAGES_YES = 1;

    public const WATERMARK_MODE_NO = 0;
    public const WATERMARK_MODE_YES = 1;

    public const WATERMARK_POSITION_TOP_RIGHT = 0;
    public const WATERMARK_POSITION_MIDDLE = 1;
    public const WATERMARK_POSITION_BOTTOM_RIGHT = 2;
    public const WATERMARK_POSITION_TOP_LEFT = 3;
    public const WATERMARK_POSITION_BOTTOM_LEFT = 4;
    public const WATERMARK_POSITION_TILE = 5;
    public const WATERMARK_POSITION_STRETCH = 6;

    public const WATERMARK_TRANSPARENT_MODE_NO = 0;
    public const WATERMARK_TRANSPARENT_MODE_YES = 1;

    public const WATERMARK_OPACITY_LEVEL = [10, 20, 30, 40, 50, 60, 70, 80, 90];
    public const WATERMARK_OPACITY_LEVEL_DEFAULT = 30;

    public const WATERMARK_CACHE_TIME = 604800; // 7 days

    public const INSTRUCTION_TYPE_MAGENTO_STATIC_BLOCK_IN_DESCRIPTION_CHANGED = 'magento_static_block_in_description_changed';

    protected $driverPool;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Description\Source[]
     */
    private $descriptionSourceModels = [];

    //########################################

    public function __construct(
        \Magento\Framework\Filesystem\DriverPool $driverPool,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->driverPool = $driverPool;
        parent::__construct(
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(DescriptionResource::class);
    }

    /**
     * @return string
     */
    public function getNick()
    {
        return \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION;
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        return (bool)$this->activeRecordFactory->getObject('Ebay\Listing')
                                               ->getCollection()
                                               ->addFieldToFilter('template_description_id', $this->getId())
                                               ->getSize() ||
            (bool)$this->activeRecordFactory->getObject('Ebay_Listing_Product')
                                            ->getCollection()
                                            ->addFieldToFilter(
                                                'template_description_mode',
                                                \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE
                                            )
                                            ->addFieldToFilter('template_description_id', $this->getId())
                                            ->getSize();
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_description');

        return parent::save();
    }

    //########################################

    public function delete()
    {
        // Delete watermark if exists
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\VariablesDir $varDir */
        $varDir = $this->modelFactory->getObject(
            'VariablesDir',
            [
                'data' => [
                    'child_folder' => 'ebay/template/description/watermarks',
                ],
            ]
        );

        $watermarkPath = $varDir->getPath() . $this->getId() . '.png';

        $fileDriver = $this->driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
        if ($fileDriver->isFile($watermarkPath)) {
            $fileDriver->deleteFile($watermarkPath);
        }
        // ---------------------------------------

        $temp = parent::delete();
        $temp && $this->descriptionSourceModels = [];

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_description');

        return $temp;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     *
     * @return \Ess\M2ePro\Model\Ebay\Template\Description\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->descriptionSourceModels[$productId])) {
            return $this->descriptionSourceModels[$productId];
        }

        $this->descriptionSourceModels[$productId] = $this->modelFactory->getObject('Ebay_Template_Description_Source');
        $this->descriptionSourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->descriptionSourceModels[$productId]->setDescriptionTemplate($this->getParentObject());

        return $this->descriptionSourceModels[$productId];
    }

    //########################################

    /**
     * @return bool
     */
    public function isCustomTemplate()
    {
        return (bool)$this->getData('is_custom_template');
    }

    //########################################

    /**
     * @return int
     */
    public function getTitleMode()
    {
        return (int)$this->getData('title_mode');
    }

    /**
     * @return bool
     */
    public function isTitleModeProduct()
    {
        return $this->getTitleMode() == self::TITLE_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isTitleModeCustom()
    {
        return $this->getTitleMode() == self::TITLE_MODE_CUSTOM;
    }

    /**
     * @return array
     */
    public function getTitleSource()
    {
        return [
            'mode' => $this->getTitleMode(),
            'template' => $this->getData('title_template'),
        ];
    }

    /**
     * @return array
     */
    public function getTitleAttributes()
    {
        $attributes = [];
        $src = $this->getTitleSource();

        if ($src['mode'] == self::TITLE_MODE_PRODUCT) {
            $attributes[] = 'name';
        } else {
            $match = [];
            preg_match_all('/#([a-zA-Z_0-9]+?)#/', $src['template'], $match);
            $match && $attributes = $match[1];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getSubTitleMode()
    {
        return (int)$this->getData('subtitle_mode');
    }

    /**
     * @return bool
     */
    public function isSubTitleModeProduct()
    {
        return $this->getSubTitleMode() == self::SUBTITLE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isSubTitleModeCustom()
    {
        return $this->getSubTitleMode() == self::SUBTITLE_MODE_CUSTOM;
    }

    /**
     * @return array
     */
    public function getSubTitleSource()
    {
        return [
            'mode' => $this->getSubTitleMode(),
            'template' => $this->getData('subtitle_template'),
        ];
    }

    /**
     * @return array
     */
    public function getSubTitleAttributes()
    {
        $attributes = [];
        $src = $this->getSubTitleSource();

        if ($src['mode'] == self::SUBTITLE_MODE_CUSTOM) {
            $match = [];
            preg_match_all('/#([a-zA-Z_0-9]+?)#/', $src['template'], $match);
            $match && $attributes = $match[1];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getDescriptionMode()
    {
        return (int)$this->getData('description_mode');
    }

    /**
     * @return bool
     */
    public function isDescriptionModeProduct()
    {
        return $this->getDescriptionMode() == self::DESCRIPTION_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isDescriptionModeShort()
    {
        return $this->getDescriptionMode() == self::DESCRIPTION_MODE_SHORT;
    }

    /**
     * @return bool
     */
    public function isDescriptionModeCustom()
    {
        return $this->getDescriptionMode() == self::DESCRIPTION_MODE_CUSTOM;
    }

    /**
     * @return array
     */
    public function getDescriptionSource()
    {
        return [
            'mode' => $this->getDescriptionMode(),
            'template' => $this->getData('description_template'),
        ];
    }

    /**
     * @return array
     */
    public function getDescriptionAttributes()
    {
        $attributes = [];
        $src = $this->getDescriptionSource();

        if ($src['mode'] == self::DESCRIPTION_MODE_PRODUCT) {
            $attributes[] = 'description';
        } elseif ($src['mode'] == self::DESCRIPTION_MODE_SHORT) {
            $attributes[] = 'short_description';
        } else {
            preg_match_all('/#([a-zA-Z_0-9]+?)#|#(image|media_gallery)\[.*\]#+?/', $src['template'], $match);
            !empty($match[0]) && $attributes = array_filter(array_merge($match[1], $match[2]));
        }

        return $attributes;
    }

    //########################################

    /**
     * @return array
     */
    public function getConditionSource()
    {
        return [
            'mode' => (int)$this->getData('condition_mode'),
            'value' => (int)$this->getData('condition_value'),
            'attribute' => $this->getData('condition_attribute'),
        ];
    }

    /**
     * @return array
     */
    public function getConditionAttributes()
    {
        $attributes = [];
        $src = $this->getConditionSource();

        if ($src['mode'] == self::CONDITION_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getConditionNoteSource()
    {
        return [
            'mode' => (int)$this->getData('condition_note_mode'),
            'template' => $this->getData('condition_note_template'),
        ];
    }

    /**
     * @return array
     */
    public function getConditionNoteAttributes()
    {
        $attributes = [];
        $src = $this->getConditionNoteSource();

        if ($src['mode'] == self::CONDITION_NOTE_MODE_CUSTOM) {
            $match = [];
            preg_match_all('/#([a-zA-Z_0-9]+?)#/', $src['template'], $match);
            $match && $attributes = $match[1];
        }

        return $attributes;
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProductDetails()
    {
        return $this->getSettings('product_details');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isProductDetailsIncludeEbayDetails()
    {
        $productDetails = $this->getProductDetails();

        return isset($productDetails['include_ebay_details']) ? (bool)$productDetails['include_ebay_details'] : true;
    }

    /**
     * @return bool
     */
    public function isProductDetailsIncludeImage()
    {
        $productDetails = $this->getProductDetails();

        return isset($productDetails['include_image']) ? (bool)$productDetails['include_image'] : true;
    }

    // ---------------------------------------

    /**
     * @param int $type
     *
     * @return bool
     */
    public function isProductDetailsModeNone($type)
    {
        return $this->getProductDetailsMode($type) == self::PRODUCT_DETAILS_MODE_NONE;
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    public function isProductDetailsModeDoesNotApply($type)
    {
        return $this->getProductDetailsMode($type) == self::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY;
    }

    /**
     * @param int $type
     *
     * @return bool
     */
    public function isProductDetailsModeAttribute($type)
    {
        return $this->getProductDetailsMode($type) == self::PRODUCT_DETAILS_MODE_ATTRIBUTE;
    }

    public function getProductDetailsMode($type)
    {
        if (!in_array($type, ['brand', 'mpn'])) {
            throw new \InvalidArgumentException('Unknown Product details name');
        }

        $productDetails = $this->getProductDetails();

        if (
            !is_array($productDetails) || !isset($productDetails[$type]) ||
            !isset($productDetails[$type]['mode'])
        ) {
            return null;
        }

        return $productDetails[$type]['mode'];
    }

    public function getProductDetailAttribute($type)
    {
        if (!in_array($type, ['brand', 'mpn'])) {
            throw new \InvalidArgumentException('Unknown Product details name');
        }

        $productDetails = $this->getProductDetails();

        if (
            !is_array($productDetails) || !isset($productDetails[$type]) ||
            $this->isProductDetailsModeNone($type) || !isset($productDetails[$type]['attribute'])
        ) {
            return null;
        }

        return $productDetails[$type]['attribute'];
    }

    //########################################

    /**
     * @return bool
     */
    public function isCutLongTitles()
    {
        return (bool)$this->getData('cut_long_titles');
    }

    /**
     * @return array
     */
    public function getEnhancements()
    {
        return $this->getData('enhancement') ? explode(',', $this->getData('enhancement')) : [];
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getEditorType()
    {
        return (int)$this->getData('editor_type');
    }

    /**
     * @return bool
     */
    public function isEditorTypeSimple()
    {
        return $this->getEditorType() == self::EDITOR_TYPE_SIMPLE;
    }

    /**
     * @return bool
     */
    public function isEditorTypeTinyMce()
    {
        return $this->getEditorType() == self::EDITOR_TYPE_TINYMCE;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getGalleryType()
    {
        return (int)$this->getData('gallery_type');
    }

    /**
     * @return bool
     */
    public function isGalleryTypeEmpty()
    {
        return $this->getGalleryType() == self::GALLERY_TYPE_EMPTY;
    }

    /**
     * @return bool
     */
    public function isGalleryTypeNo()
    {
        return $this->getGalleryType() == self::GALLERY_TYPE_NO;
    }

    /**
     * @return bool
     */
    public function isGalleryTypePicture()
    {
        return $this->getGalleryType() == self::GALLERY_TYPE_PICTURE;
    }

    /**
     * @return bool
     */
    public function isGalleryTypeFeatured()
    {
        return $this->getGalleryType() == self::GALLERY_TYPE_FEATURED;
    }

    /**
     * @return bool
     */
    public function isGalleryTypePlus()
    {
        return $this->getGalleryType() == self::GALLERY_TYPE_PLUS;
    }

    //########################################

    /**
     * @return int
     */
    public function getImageMainMode()
    {
        return (int)$this->getData('image_main_mode');
    }

    /**
     * @return bool
     */
    public function isImageMainModeNone()
    {
        return $this->getImageMainMode() == self::IMAGE_MAIN_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isImageMainModeProduct()
    {
        return $this->getImageMainMode() == self::IMAGE_MAIN_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isImageMainModeAttribute()
    {
        return $this->getImageMainMode() == self::IMAGE_MAIN_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getImageMainSource()
    {
        return [
            'mode' => $this->getImageMainMode(),
            'attribute' => $this->getData('image_main_attribute'),
        ];
    }

    /**
     * @return array
     */
    public function getImageMainAttributes()
    {
        $attributes = [];
        $src = $this->getImageMainSource();

        if ($src['mode'] == self::IMAGE_MAIN_MODE_PRODUCT) {
            $attributes[] = 'image';
        } elseif ($src['mode'] == self::IMAGE_MAIN_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getGalleryImagesMode()
    {
        return (int)$this->getData('gallery_images_mode');
    }

    /**
     * @return bool
     */
    public function isGalleryImagesModeNone()
    {
        return $this->getGalleryImagesMode() == self::GALLERY_IMAGES_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isGalleryImagesModeProduct()
    {
        return $this->getGalleryImagesMode() == self::GALLERY_IMAGES_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isGalleryImagesModeAttribute()
    {
        return $this->getGalleryImagesMode() == self::GALLERY_IMAGES_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getGalleryImagesSource()
    {
        return [
            'mode' => $this->getGalleryImagesMode(),
            'attribute' => $this->getData('gallery_images_attribute'),
            'limit' => $this->getData('gallery_images_limit'),
        ];
    }

    /**
     * @return array
     */
    public function getGalleryImagesAttributes()
    {
        $attributes = [];
        $src = $this->getGalleryImagesSource();

        if ($src['mode'] == self::GALLERY_IMAGES_MODE_PRODUCT) {
            $attributes[] = 'media_gallery';
        } elseif ($src['mode'] == self::GALLERY_IMAGES_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getVariationImagesMode()
    {
        return (int)$this->getData('variation_images_mode');
    }

    /**
     * @return bool
     */
    public function isVariationImagesModeNone()
    {
        return $this->getVariationImagesMode() == self::VARIATION_IMAGES_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isVariationImagesModeProduct()
    {
        return $this->getVariationImagesMode() == self::VARIATION_IMAGES_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isVariationImagesModeAttribute()
    {
        return $this->getVariationImagesMode() == self::VARIATION_IMAGES_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getVariationImagesSource()
    {
        return [
            'mode' => $this->getVariationImagesMode(),
            'attribute' => $this->getData('variation_images_attribute'),
            'limit' => $this->getData('variation_images_limit'),
        ];
    }

    /**
     * @return array
     */
    public function getVariationImagesAttributes()
    {
        $attributes = [];
        $src = $this->getVariationImagesSource();

        if ($src['mode'] == self::VARIATION_IMAGES_MODE_PRODUCT) {
            $attributes[] = 'media_gallery';
        } elseif ($src['mode'] == self::VARIATION_IMAGES_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    public function getDefaultImageUrl()
    {
        return $this->getData('default_image_url');
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getDecodedVariationConfigurableImages()
    {
        return \Ess\M2ePro\Helper\Json::decode($this->getData('variation_configurable_images'));
    }

    /**
     * @return bool
     */
    public function isVariationConfigurableImages()
    {
        $images = $this->getDecodedVariationConfigurableImages();

        return !empty($images);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isUseSupersizeImagesEnabled()
    {
        return (bool)$this->getData('use_supersize_images');
    }

    //########################################

    /**
     * @return bool
     */
    public function isWatermarkEnabled()
    {
        return (bool)$this->getData('watermark_mode');
    }

    public function getWatermarkImage()
    {
        return $this->getData('watermark_image');
    }

    public function getWatermarkHash()
    {
        $settingNamePath = [
            'hashes',
            'current',
        ];

        return $this->getSetting('watermark_settings', $settingNamePath);
    }

    public function getWatermarkPreviousHash()
    {
        $settingNamePath = [
            'hashes',
            'previous',
        ];

        return $this->getSetting('watermark_settings', $settingNamePath);
    }

    public function updateWatermarkHashes()
    {
        $settings = $this->getSettings('watermark_settings');

        if (isset($settings['hashes']['current'])) {
            $settings['hashes']['previous'] = $settings['hashes']['current'];
        } else {
            $settings['hashes']['previous'] = '';
        }

        $settings['hashes']['current'] = substr(sha1(microtime()), 0, 5);

        $this->setSettings('watermark_settings', $settings);

        return $this;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getWatermarkPosition()
    {
        return (int)$this->getSetting('watermark_settings', 'position');
    }

    /**
     * @return int
     */
    public function getWatermarkTransparentMode()
    {
        return (int)$this->getSetting('watermark_settings', 'transparent');
    }

    /**
     * @return int
     */
    public function getWatermarkOpacityLevel()
    {
        return (int)$this->getSetting(
            'watermark_settings',
            'opacity_level',
            \Ess\M2ePro\Model\Ebay\Template\Description::WATERMARK_OPACITY_LEVEL_DEFAULT
        );
    }

    public function isVideoModeNone(): bool
    {
        return $this->getVideoMode() === self::VIDEO_MODE_NONE;
    }

    public function isVideoModeAttribute(): bool
    {
        return $this->getVideoMode() === self::VIDEO_MODE_ATTRIBUTE;
    }

    private function getVideoMode(): int
    {
        return (int)$this->getDataByKey(DescriptionResource::COLUMN_VIDEO_MODE);
    }

    public function getVideoAttribute(): ?string
    {
        return $this->getDataByKey(DescriptionResource::COLUMN_VIDEO_ATTRIBUTE);
    }

    // ---------------------------------------

    public function getComplianceDocuments(): array
    {
        $complianceDocuments = $this->getData(DescriptionResource::COLUMN_COMPLIANCE_DOCUMENTS);
        if (empty($complianceDocuments)) {
            return [];
        }

        return json_decode($complianceDocuments, true);
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isWatermarkTransparentEnabled()
    {
        return (bool)$this->getWatermarkTransparentMode();
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }
}
