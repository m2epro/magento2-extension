<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template;

/**
 * @method \Ess\M2ePro\Model\Template\Description getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description getResource()
 */
class Description extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    const TITLE_MODE_PRODUCT = 0;
    const TITLE_MODE_CUSTOM  = 1;

    const SUBTITLE_MODE_NONE     = 0;
    const SUBTITLE_MODE_CUSTOM   = 1;

    const DESCRIPTION_MODE_PRODUCT = 0;
    const DESCRIPTION_MODE_SHORT   = 1;
    const DESCRIPTION_MODE_CUSTOM  = 2;

    const CONDITION_MODE_EBAY       = 0;
    const CONDITION_MODE_ATTRIBUTE  = 1;
    const CONDITION_MODE_NONE       = 2;

    const CONDITION_EBAY_NEW                        = 1000;
    const CONDITION_EBAY_NEW_OTHER                  = 1500;
    const CONDITION_EBAY_NEW_WITH_DEFECT            = 1750;
    const CONDITION_EBAY_MANUFACTURER_REFURBISHED   = 2000;
    const CONDITION_EBAY_SELLER_REFURBISHED         = 2500;
    const CONDITION_EBAY_USED                       = 3000;
    const CONDITION_EBAY_VERY_GOOD                  = 4000;
    const CONDITION_EBAY_GOOD                       = 5000;
    const CONDITION_EBAY_ACCEPTABLE                 = 6000;
    const CONDITION_EBAY_NOT_WORKING                = 7000;

    const CONDITION_NOTE_MODE_NONE    = 0;
    const CONDITION_NOTE_MODE_CUSTOM  = 1;

    const EDITOR_TYPE_SIMPLE    = 0;
    const EDITOR_TYPE_TINYMCE   = 1;

    const CUT_LONG_TITLE_DISABLED = 0;
    const CUT_LONG_TITLE_ENABLED  = 1;

    const PRODUCT_DETAILS_MODE_NONE           = 0;
    const PRODUCT_DETAILS_MODE_DOES_NOT_APPLY = 1;
    const PRODUCT_DETAILS_MODE_ATTRIBUTE      = 2;

    const HIT_COUNTER_NONE          = 'NoHitCounter';
    const HIT_COUNTER_BASIC_STYLE   = 'BasicStyle';
    const HIT_COUNTER_GREEN_LED     = 'GreenLED';
    const HIT_COUNTER_HIDDEN_STYLE  = 'HiddenStyle';
    const HIT_COUNTER_HONESTY_STYLE = 'HonestyStyle';
    const HIT_COUNTER_RETRO_STYLE   = 'RetroStyle';

    const GALLERY_TYPE_EMPTY    = 4;
    const GALLERY_TYPE_NO       = 0;
    const GALLERY_TYPE_PICTURE  = 1;
    const GALLERY_TYPE_PLUS     = 2;
    const GALLERY_TYPE_FEATURED = 3;

    const IMAGE_MAIN_MODE_NONE       = 0;
    const IMAGE_MAIN_MODE_PRODUCT    = 1;
    const IMAGE_MAIN_MODE_ATTRIBUTE  = 2;

    const GALLERY_IMAGES_MODE_NONE      = 0;
    const GALLERY_IMAGES_MODE_PRODUCT   = 1;
    const GALLERY_IMAGES_MODE_ATTRIBUTE = 2;

    const VARIATION_IMAGES_MODE_NONE      = 0;
    const VARIATION_IMAGES_MODE_PRODUCT   = 1;
    const VARIATION_IMAGES_MODE_ATTRIBUTE = 2;

    const USE_SUPERSIZE_IMAGES_NO  = 0;
    const USE_SUPERSIZE_IMAGES_YES = 1;

    const WATERMARK_MODE_NO   = 0;
    const WATERMARK_MODE_YES  = 1;

    const WATERMARK_POSITION_TOP = 0;
    const WATERMARK_POSITION_MIDDLE = 1;
    const WATERMARK_POSITION_BOTTOM = 2;

    const WATERMARK_SCALE_MODE_NONE = 0;
    const WATERMARK_SCALE_MODE_IN_WIDTH = 1;
    const WATERMARK_SCALE_MODE_STRETCH = 2;

    const WATERMARK_TRANSPARENT_MODE_NO = 0;
    const WATERMARK_TRANSPARENT_MODE_YES = 1;

    const WATERMARK_CACHE_TIME = 604800; // 7 days
    const GALLERY_IMAGES_COUNT_MAX = 11;

    protected $driverPool;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Description\Source[]
     */
    private $descriptionSourceModels = array();

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
    )
    {
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
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Template\Description');
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
                            ->addFieldToFilter('template_description_mode',
                                               \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE)
                            ->addFieldToFilter('template_description_id', $this->getId())
                            ->getSize() ||
               (bool)$this->activeRecordFactory->getObject('Ebay\Listing\Product')
                            ->getCollection()
                            ->addFieldToFilter('template_description_mode',
                                               \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE)
                            ->addFieldToFilter('template_description_id', $this->getId())
                            ->getSize();
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('template_description');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        // Delete watermark if exists
        // ---------------------------------------
        /** @var \Ess\M2ePro\Model\VariablesDir $varDir */
        $varDir = $this->modelFactory->getObject('VariablesDir', ['data' => [
            'child_folder' => 'ebay/template/description/watermarks'
        ]]);

        $watermarkPath = $varDir->getPath().$this->getId().'.png';

        $fileDriver = $this->driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
        if ($fileDriver->isFile($watermarkPath)) {
            $fileDriver->deleteFile($watermarkPath);
        }
        // ---------------------------------------

        $temp = parent::delete();
        $temp && $this->descriptionSourceModels = array();

        $this->getHelper('Data\Cache\Permanent')->removeTagValues('template_description');

        return $temp;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Ebay\Template\Description\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->descriptionSourceModels[$productId])) {
            return $this->descriptionSourceModels[$productId];
        }

        $this->descriptionSourceModels[$productId] = $this->modelFactory->getObject('Ebay\Template\Description\Source');
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
        return array(
            'mode'     => $this->getTitleMode(),
            'template' => $this->getData('title_template')
        );
    }

    /**
     * @return array
     */
    public function getTitleAttributes()
    {
        $attributes = array();
        $src = $this->getTitleSource();

        if ($src['mode'] == self::TITLE_MODE_PRODUCT) {
            $attributes[] = 'name';
        } else {
            $match = array();
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
        return array(
            'mode'     => $this->getSubTitleMode(),
            'template' => $this->getData('subtitle_template')
        );
    }

    /**
     * @return array
     */
    public function getSubTitleAttributes()
    {
        $attributes = array();
        $src = $this->getSubTitleSource();

        if ($src['mode'] == self::SUBTITLE_MODE_CUSTOM) {
            $match = array();
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
        return array(
            'mode'     => $this->getDescriptionMode(),
            'template' => $this->getData('description_template')
        );
    }

    /**
     * @return array
     */
    public function getDescriptionAttributes()
    {
        $attributes = array();
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
        return array(
            'mode'      => (int)$this->getData('condition_mode'),
            'value'     => (int)$this->getData('condition_value'),
            'attribute' => $this->getData('condition_attribute')
        );
    }

    /**
     * @return array
     */
    public function getConditionAttributes()
    {
        $attributes = array();
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
        return array(
            'mode'      => (int)$this->getData('condition_note_mode'),
            'template'  => $this->getData('condition_note_template')
        );
    }

    /**
     * @return array
     */
    public function getConditionNoteAttributes()
    {
        $attributes = array();
        $src = $this->getConditionNoteSource();

        if ($src['mode'] == self::CONDITION_NOTE_MODE_CUSTOM) {
            $match = array();
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
    public function isProductDetailsIncludeDescription()
    {
        $productDetails = $this->getProductDetails();
        return isset($productDetails['include_description']) ? (bool)$productDetails['include_description'] : true;
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
     * @return bool
     */
    public function isProductDetailsModeNone($type)
    {
        return $this->getProductDetailsMode($type) == self::PRODUCT_DETAILS_MODE_NONE;
    }

    /**
     * @param int $type
     * @return bool
     */
    public function isProductDetailsModeDoesNotApply($type)
    {
        return $this->getProductDetailsMode($type) == self::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY;
    }

    /**
     * @param int $type
     * @return bool
     */
    public function isProductDetailsModeAttribute($type)
    {
        return $this->getProductDetailsMode($type) == self::PRODUCT_DETAILS_MODE_ATTRIBUTE;
    }

    public function getProductDetailsMode($type)
    {
        if (!in_array($type, array('isbn', 'epid', 'upc', 'ean', 'brand', 'mpn'))) {
            throw new \InvalidArgumentException('Unknown Product details name');
        }

        $productDetails = $this->getProductDetails();

        if (!is_array($productDetails) || !isset($productDetails[$type]) ||
            !isset($productDetails[$type]['mode'])) {
            return NULL;
        }

        return $productDetails[$type]['mode'];
    }

    public function getProductDetailAttribute($type)
    {
        if (!in_array($type, array('isbn', 'epid', 'upc', 'ean', 'brand', 'mpn'))) {
            throw new \InvalidArgumentException('Unknown Product details name');
        }

        $productDetails = $this->getProductDetails();

        if (!is_array($productDetails) || !isset($productDetails[$type]) ||
            $this->isProductDetailsModeNone($type) || !isset($productDetails[$type]['attribute'])) {
            return NULL;
        }

        return $productDetails[$type]['attribute'];
    }

    /**
     * @return array
     */
    public function getProductDetailAttributes()
    {
        $attributes = array();

        $temp = $this->getProductDetailAttribute('isbn');
        $temp && $attributes[] = $temp;

        $temp = $this->getProductDetailAttribute('epid');
        $temp && $attributes[] = $temp;

        $temp = $this->getProductDetailAttribute('upc');
        $temp && $attributes[] = $temp;

        $temp = $this->getProductDetailAttribute('ean');
        $temp && $attributes[] = $temp;

        $temp = $this->getProductDetailAttribute('brand');
        $temp && $attributes[] = $temp;

        $temp = $this->getProductDetailAttribute('mpn');
        $temp && $attributes[] = $temp;

        return $attributes;
    }

    //########################################

    /**
     * @return bool
     */
    public function isCutLongTitles()
    {
        return (bool)$this->getData('cut_long_titles');
    }

    public function getHitCounterType()
    {
        return $this->getData('hit_counter');
    }

    /**
     * @return array
     */
    public function getEnhancements()
    {
        return $this->getData('enhancement') ? explode(',', $this->getData('enhancement')) : array();
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
        return array(
            'mode'     => $this->getImageMainMode(),
            'attribute' => $this->getData('image_main_attribute')
        );
    }

    /**
     * @return array
     */
    public function getImageMainAttributes()
    {
        $attributes = array();
        $src = $this->getImageMainSource();

        if ($src['mode'] == self::IMAGE_MAIN_MODE_PRODUCT) {
            $attributes[] = 'image';
        } else if ($src['mode'] == self::IMAGE_MAIN_MODE_ATTRIBUTE) {
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
        return array(
            'mode'     => $this->getGalleryImagesMode(),
            'attribute' => $this->getData('gallery_images_attribute'),
            'limit' => $this->getData('gallery_images_limit')
        );
    }

    /**
     * @return array
     */
    public function getGalleryImagesAttributes()
    {
        $attributes = array();
        $src = $this->getGalleryImagesSource();

        if ($src['mode'] == self::GALLERY_IMAGES_MODE_PRODUCT) {
            $attributes[] = 'media_gallery';
        } else if ($src['mode'] == self::GALLERY_IMAGES_MODE_ATTRIBUTE) {
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
        return array(
            'mode'     => $this->getVariationImagesMode(),
            'attribute' => $this->getData('variation_images_attribute'),
            'limit' => $this->getData('variation_images_limit')
        );
    }

    /**
     * @return array
     */
    public function getVariationImagesAttributes()
    {
        $attributes = array();
        $src = $this->getVariationImagesSource();

        if ($src['mode'] == self::VARIATION_IMAGES_MODE_PRODUCT) {
            $attributes[] = 'media_gallery';
        } else if ($src['mode'] == self::VARIATION_IMAGES_MODE_ATTRIBUTE) {
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
        return $this->getHelper('Data')->jsonDecode($this->getData('variation_configurable_images'));
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
        $settingNamePath = array(
            'hashes',
            'current'
        );

        return $this->getSetting('watermark_settings', $settingNamePath);
    }

    public function getWatermarkPreviousHash()
    {
        $settingNamePath = array(
            'hashes',
            'previous'
        );

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
    public function getWatermarkScaleMode()
    {
        return (int)$this->getSetting('watermark_settings', 'scale');
    }

    /**
     * @return int
     */
    public function getWatermarkTransparentMode()
    {
        return (int)$this->getSetting('watermark_settings', 'transparent');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isWatermarkPositionTop()
    {
        return $this->getWatermarkPosition() == self::WATERMARK_POSITION_TOP;
    }

    /**
     * @return bool
     */
    public function isWatermarkPositionMiddle()
    {
        return $this->getWatermarkPosition() == self::WATERMARK_POSITION_MIDDLE;
    }

    /**
     * @return bool
     */
    public function isWatermarkPositionBottom()
    {
        return $this->getWatermarkPosition() == self::WATERMARK_POSITION_BOTTOM;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isWatermarkScaleModeNone()
    {
        return $this->getWatermarkScaleMode() == self::WATERMARK_SCALE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isWatermarkScaleModeInWidth()
    {
        return $this->getWatermarkScaleMode() == self::WATERMARK_SCALE_MODE_IN_WIDTH;
    }

    /**
     * @return bool
     */
    public function isWatermarkScaleModeStretch()
    {
        return $this->getWatermarkScaleMode() == self::WATERMARK_SCALE_MODE_STRETCH;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isWatermarkTransparentEnabled()
    {
        return (bool)$this->getWatermarkTransparentMode();
    }

    //########################################

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        return array_unique(array_merge(
            $this->getTitleAttributes(),
            $this->getSubTitleAttributes(),
            $this->getDescriptionAttributes(),
            $this->getImageMainAttributes(),
            $this->getGalleryImagesAttributes(),
            $this->getVariationImagesAttributes()
        ));
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        return array_unique(array_merge(
            $this->getTitleAttributes(),
            $this->getSubTitleAttributes(),
            $this->getDescriptionAttributes(),
            $this->getConditionAttributes(),
            $this->getConditionNoteAttributes(),
            $this->getProductDetailAttributes(),
            $this->getImageMainAttributes(),
            $this->getGalleryImagesAttributes(),
            $this->getVariationImagesAttributes()
        ));
    }

    //########################################

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        return array(

            'title_mode' => self::TITLE_MODE_PRODUCT,
            'title_template' => '',

            'subtitle_mode' => self::SUBTITLE_MODE_NONE,
            'subtitle_template' => '',

            'description_mode' => self::DESCRIPTION_MODE_PRODUCT,
            'description_template' => '',

            'condition_mode' => self::CONDITION_MODE_EBAY,
            'condition_value' => self::CONDITION_EBAY_NEW,
            'condition_attribute' => '',

            'condition_note_mode' => self::CONDITION_NOTE_MODE_NONE,
            'condition_note_template' => '',

            'product_details' => $this->getHelper('Data')->jsonEncode(array(
                'isbn'  => array('mode' => self::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''),
                'epid'  => array('mode' => self::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''),
                'upc'   => array('mode' => self::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''),
                'ean'   => array('mode' => self::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''),
                'brand' => array('mode' => self::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''),
                'mpn'   => array('mode' => self::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY, 'attribute' => ''),
                'include_description' => 1,
                'include_image'       => 1,
            )),

            'editor_type' => self::EDITOR_TYPE_SIMPLE,
            'cut_long_titles' => self::CUT_LONG_TITLE_ENABLED,
            'hit_counter' => self::HIT_COUNTER_NONE,

            'enhancement' => '',
            'gallery_type' => self::GALLERY_TYPE_EMPTY,

            'image_main_mode' => self::IMAGE_MAIN_MODE_PRODUCT,
            'image_main_attribute' => '',
            'gallery_images_mode' => self::GALLERY_IMAGES_MODE_NONE,
            'gallery_images_limit' => 3,
            'gallery_images_attribute' => '',
            'variation_images_mode' => self::VARIATION_IMAGES_MODE_PRODUCT,
            'variation_images_limit' => 1,
            'variation_images_attribute' => '',
            'default_image_url' => '',

            'variation_configurable_images' => $this->getHelper('Data')->jsonEncode(array()),
            'use_supersize_images' => self::USE_SUPERSIZE_IMAGES_NO,

            'watermark_mode' => self::WATERMARK_MODE_NO,

            'watermark_settings' => $this->getHelper('Data')->jsonEncode(array(
                'position' => self::WATERMARK_POSITION_TOP,
                'scale' => self::WATERMARK_SCALE_MODE_NONE,
                'transparent' => self::WATERMARK_TRANSPARENT_MODE_NO,

                'hashes' => array(
                    'current'  => '',
                    'previous' => '',
                )
            )),

            'watermark_image' => NULL
        );
    }

    //########################################

    /**
     * @param bool $asArrays
     * @param string|array $columns
     * @return array
     */
    public function getAffectedListingsProducts($asArrays = true, $columns = '*')
    {
        $templateManager = $this->modelFactory->getObject('Ebay\Template\Manager');
        $templateManager->setTemplate(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION);

        $listingsProducts = $templateManager->getAffectedOwnerObjects(
           \Ess\M2ePro\Model\Ebay\Template\Manager::OWNER_LISTING_PRODUCT, $this->getId(), $asArrays, $columns
        );

        $listings = $templateManager->getAffectedOwnerObjects(
           \Ess\M2ePro\Model\Ebay\Template\Manager::OWNER_LISTING, $this->getId(), false
        );

        foreach ($listings as $listing) {

            $tempListingsProducts = $listing->getChildObject()
                                            ->getAffectedListingsProductsByTemplate(
                                               \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION,
                                                $asArrays, $columns
                                            );

            foreach ($tempListingsProducts as $listingProduct) {
                if (!isset($listingsProducts[$listingProduct['id']])) {
                    $listingsProducts[$listingProduct['id']] = $listingProduct;
                }
            }
        }

        return $listingsProducts;
    }

    public function setSynchStatusNeed($newData, $oldData)
    {
        $listingsProducts = $this->getAffectedListingsProducts(true, array('id'));
        if (empty($listingsProducts)) {
            return;
        }

        $this->getResource()->setSynchStatusNeed($newData,$oldData,$listingsProducts);
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}