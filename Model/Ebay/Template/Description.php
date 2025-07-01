<?php

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
    public const CONDITION_EBAY_PRE_OWNED_EXCELLENT = 2990;
    public const CONDITION_EBAY_USED_EXCELLENT = 3000;
    public const CONDITION_EBAY_PRE_OWNED_FAIR = 3010;
    public const CONDITION_EBAY_VERY_GOOD = 4000;
    public const CONDITION_EBAY_GOOD = 5000;
    public const CONDITION_EBAY_ACCEPTABLE = 6000;
    public const CONDITION_EBAY_NOT_WORKING = 7000;
    public const CONDITION_EBAY_GRADED = 27501;
    public const CONDITION_EBAY_UNGRADED = 4001;

    public const CONDITION_DESCRIPTOR_MODE_NONE = 0;
    public const CONDITION_DESCRIPTOR_MODE_EBAY = 1;
    public const CONDITION_DESCRIPTOR_MODE_CUSTOM = 2;
    public const CONDITION_DESCRIPTOR_MODE_ATTRIBUTE = 3;

    public const CONDITION_DESCRIPTOR_ID_PROFESSIONAL_GRADER = 27501;
    public const CONDITION_DESCRIPTOR_ID_GRADE = 27502;
    public const CONDITION_DESCRIPTOR_ID_CERTIFICATION_NUMBER = 27503;
    public const CONDITION_DESCRIPTOR_ID_CARD_CONDITION = 40001;

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
    public const VIDEO_MODE_CUSTOM_VALUE = 1;
    public const VIDEO_MODE_ATTRIBUTE = 2;

    public const COMPLIANCE_DOCUMENTS_MODE_CUSTOM_VALUE = 1;
    public const COMPLIANCE_DOCUMENTS_MODE_ATTRIBUTE = 2;

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

    private \Magento\Framework\Filesystem\DriverPool $driverPool;
    /** @var \Ess\M2ePro\Model\Ebay\Template\Description\Source[] */
    private array $descriptionSourceModels = [];

    public function __construct(
        \Magento\Framework\Filesystem\DriverPool $driverPool,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
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

        $this->driverPool = $driverPool;
    }

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

    // ----------------------------------------

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

    // ----------------------------------------

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_description');

        return parent::save();
    }

    // ----------------------------------------

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

    // ----------------------------------------

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

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isCustomTemplate()
    {
        return (bool)$this->getData('is_custom_template');
    }

    // ----------------------------------------

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

    // ----------------------------------------

    public function isConditionProfessionalGraderIdModeAttribute(): bool
    {
        return $this->getConditionProfessionalGraderIdMode() === self::CONDITION_DESCRIPTOR_MODE_ATTRIBUTE;
    }

    public function getConditionProfessionalGraderIdAttribute(): string
    {
        if (!$this->isConditionProfessionalGraderIdModeAttribute()) {
            throw new \RuntimeException('Invalid Condition Professional Grader Mode');
        }

        $attr = $this->getDataByKey(DescriptionResource::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_ATTRIBUTE);
        if ($attr === null) {
            throw new \RuntimeException('Condition Professional Grader Attribute not found');
        }

        return $attr;
    }

    public function isConditionProfessionalGraderIdModeEbay(): bool
    {
        return $this->getConditionProfessionalGraderIdMode() === self::CONDITION_DESCRIPTOR_MODE_EBAY;
    }

    public function getConditionProfessionalGraderIdValue(): int
    {
        if (!$this->isConditionProfessionalGraderIdModeEbay()) {
            throw new \RuntimeException('Invalid Professional Graded ID mode');
        }

        $val = $this->getDataByKey(
            DescriptionResource::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_VALUE
        );

        if ($val === null) {
            throw new \RuntimeException('Condition Professional Grader ID Value not found');
        }

        return (int)$val;
    }

    private function getConditionProfessionalGraderIdMode(): int
    {
        return (int)$this->getDataByKey(DescriptionResource::COLUMN_CONDITION_PROFESSIONAL_GRADER_ID_MODE);
    }

    public static function getConditionalProfessionalGraderIdLabelMap(): array
    {
        return [
            275010 => 'Professional Sports Authenticator (PSA)',
            275011 => 'Beckett Collectors Club Grading (BCCG)',
            275012 => 'Beckett Vintage Grading (BVG)',
            275013 => 'Beckett Grading Services (BGS)',
            275014 => 'Certified Sports Guaranty (CSG)',
            275015 => 'Certified Guaranty Company (CGC)',
            275016 => 'Sportscard Guaranty Corporation (SGC)',
            275017 => 'K Sportscard Authentication (KSA)',
            275018 => 'Gem Mint Authentication (GMA)',
            275019 => 'Hybrid Grading Approach (HGA)',
            2750110 => 'International Sports Authentication (ISA)',
            2750111 => 'Professional Card Authenticator (PCA)',
            2750112 => 'Gold Standard Grading (GSG)',
            2750113 => 'Platin Grading Service (PGS)',
            2750114 => 'MNT Grading (MNT)',
            2750115 => 'Technical Authentication & Grading (TAG)',
            2750116 => 'Rare Edition (Rare)',
            2750117 => 'Revolution Card Grading (RCG)',
            2750118 => 'Premier Card Grading (PCG)',
            2750119 => 'Ace Grading (Ace)',
            2750120 => 'Card Grading Australia (CGA)',
            2750121 => 'Trading Card Grading (TCG)',
            2750122 => 'ARK Grading (ARK)',
            2750123 => 'Other',
        ];
    }

    // ----------------------------------------

    public function isConditionGradeIdModeAttribute(): bool
    {
        return $this->getConditionGradeIdMode() === self::CONDITION_DESCRIPTOR_MODE_ATTRIBUTE;
    }

    public function getConditionGradeIdAttribute(): string
    {
        if (!$this->isConditionGradeIdModeAttribute()) {
            throw new \RuntimeException('Invalid Condition Grade ID mode');
        }

        $attr = $this->getDataByKey(
            DescriptionResource::COLUMN_CONDITION_GRADE_ID_ATTRIBUTE
        );

        if ($attr === null) {
            throw new \RuntimeException('Condition Grade ID Attribute not found');
        }

        return $attr;
    }

    public function isConditionGradeIdModeEbay(): bool
    {
        return $this->getConditionGradeIdMode() === self::CONDITION_DESCRIPTOR_MODE_EBAY;
    }

    public function getConditionGradeIdValue(): int
    {
        if (!$this->isConditionGradeIdModeEbay()) {
            throw new \RuntimeException('Invalid Condition Grade ID mode');
        }

        $val = $this->getDataByKey(
            DescriptionResource::COLUMN_CONDITION_GRADE_ID_VALUE
        );

        if ($val === null) {
            throw new \RuntimeException('Condition Grade ID Value not found');
        }

        return (int)$val;
    }

    private function getConditionGradeIdMode(): int
    {
        return (int)$this->getDataByKey(DescriptionResource::COLUMN_CONDITION_GRADE_ID_MODE);
    }

    public static function getConditionalGradeIdLabelMap(): array
    {
        return [
            275020 => '10',
            275021 => '9.5',
            275022 => '9',
            275023 => '8.5',
            275024 => '8',
            275025 => '7.5',
            275026 => '7',
            275027 => '6.5',
            275028 => '6',
            275029 => '5.5',
            2750210 => '5',
            2750211 => '4.5',
            2750212 => '4',
            2750213 => '3.5',
            2750214 => '3',
            2750215 => '2.5',
            2750216 => '2',
            2750217 => '1.5',
            2750218 => '1',
            2750219 => 'Authentic',
            2750220 => 'Authentic Altered',
            2750221 => 'Authentic - Trimmed',
            2750222 => 'Authentic - Coloured',
        ];
    }

    // ----------------------------------------

    public function isConditionGradeCertificationModeAttribute(): bool
    {
        return $this->getConditionGradeCertificationMode() === self::CONDITION_DESCRIPTOR_MODE_ATTRIBUTE;
    }

    public function getConditionGradeCertificationAttribute(): string
    {
        if (!$this->isConditionGradeCertificationModeAttribute()) {
            throw new \RuntimeException('Invalid Condition Grade Certification mode');
        }

        $attr = $this->getDataByKey(DescriptionResource::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_ATTRIBUTE);
        if ($attr === null) {
            throw new \RuntimeException('Condition Grade Certification Attribute not found');
        }

        return $attr;
    }

    public function isConditionGradeCertificationModeCustom(): bool
    {
        return $this->getConditionGradeCertificationMode() === self::CONDITION_DESCRIPTOR_MODE_CUSTOM;
    }

    public function getConditionGradeCertificationCustomValue(): string
    {
        if (!$this->isConditionGradeCertificationModeCustom()) {
            throw new \RuntimeException('Invalid Condition Grade Certification mode');
        }

        $val = $this->getDataByKey(DescriptionResource::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_CUSTOM_VALUE);
        if (empty($val)) {
            throw new \RuntimeException('Condition Grade Certification Custom Value not found');
        }

        return $val;
    }

    private function getConditionGradeCertificationMode(): int
    {
        return (int)$this->getDataByKey(DescriptionResource::COLUMN_CONDITION_GRADE_CERTIFICATION_NUMBER_MODE);
    }

    // ----------------------------------------

    public function isConditionGradeCardConditionModeAttribute(): bool
    {
        return $this->getConditionGradeCardConditionMode() === self::CONDITION_DESCRIPTOR_MODE_ATTRIBUTE;
    }

    public function getConditionGradeCardConditionIdAttribute(): string
    {
        if (!$this->isConditionGradeCardConditionModeAttribute()) {
            throw new \RuntimeException('Invalid Condition Grade Card Condition Mode');
        }

        $attr = $this->getDataByKey(
            DescriptionResource::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_ATTRIBUTE
        );

        if ($attr === null) {
            throw new \RuntimeException('Grade Card Condition not found');
        }

        return $attr;
    }

    public function isConditionGradeCardConditionEbay(): bool
    {
        return $this->getConditionGradeCardConditionMode() === self::CONDITION_DESCRIPTOR_MODE_EBAY;
    }

    public function getConditionGradeCardConditionIdValue(): int
    {
        if (!$this->isConditionGradeCardConditionEbay()) {
            throw new \RuntimeException('Invalid Condition Grade Card Condition Mode');
        }

        $id = $this->getDataByKey(
            DescriptionResource::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_VALUE
        );

        if ($id === null) {
            throw new \RuntimeException('Grade Card Condition Value not found');
        }

        return (int)$id;
    }

    private function getConditionGradeCardConditionMode(): int
    {
        return (int)$this->getDataByKey(DescriptionResource::COLUMN_CONDITION_GRADE_CARD_CONDITION_ID_MODE);
    }

    public static function getConditionalCardConditionIdLabelMap(): array
    {
        return [
            400010 => 'Near Mint or Better',
            400011 => 'Excellent',
            400012 => 'Very Good',
            400013 => 'Poor',
            400015 => 'Lightly Played (Excellent)',
            400016 => 'Moderately Played (Very Good)',
            400017 => 'Heavily Played (Poor)',
        ];
    }

    // ----------------------------------------

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

    // ----------------------------------------

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

    // ----------------------------------------

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

    // ----------------------------------------

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

    // ----------------------------------------

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

    public function isVideoModeCustomValue(): bool
    {
        return $this->getVideoMode() === self::VIDEO_MODE_CUSTOM_VALUE;
    }

    private function getVideoMode(): int
    {
        return (int)$this->getDataByKey(DescriptionResource::COLUMN_VIDEO_MODE);
    }

    public function getVideoAttribute(): ?string
    {
        return $this->getDataByKey(DescriptionResource::COLUMN_VIDEO_ATTRIBUTE);
    }

    public function getVideoCustomValue(): ?string
    {
        return $this->getDataByKey(DescriptionResource::COLUMN_VIDEO_CUSTOM_VALUE);
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
