<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon;

/**
 * @method \Ess\M2ePro\Model\Listing getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Listing getResource()
 */
class Listing extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    const SKU_MODE_PRODUCT_ID       = 3;
    const SKU_MODE_DEFAULT          = 1;
    const SKU_MODE_CUSTOM_ATTRIBUTE = 2;

    const SKU_MODIFICATION_MODE_NONE     = 0;
    const SKU_MODIFICATION_MODE_PREFIX   = 1;
    const SKU_MODIFICATION_MODE_POSTFIX  = 2;
    const SKU_MODIFICATION_MODE_TEMPLATE = 3;

    const GENERATE_SKU_MODE_NO  = 0;
    const GENERATE_SKU_MODE_YES = 1;

    const GENERAL_ID_MODE_NOT_SET          = 0;
    const GENERAL_ID_MODE_CUSTOM_ATTRIBUTE = 1;

    const WORLDWIDE_ID_MODE_NOT_SET          = 0;
    const WORLDWIDE_ID_MODE_CUSTOM_ATTRIBUTE = 1;

    const SEARCH_BY_MAGENTO_TITLE_MODE_NONE = 0;
    const SEARCH_BY_MAGENTO_TITLE_MODE_YES  = 1;

    const CONDITION_MODE_DEFAULT          = 1;
    const CONDITION_MODE_CUSTOM_ATTRIBUTE = 2;

    const CONDITION_NEW                    = 'New';
    const CONDITION_USED_LIKE_NEW          = 'UsedLikeNew';
    const CONDITION_USED_VERY_GOOD         = 'UsedVeryGood';
    const CONDITION_USED_GOOD              = 'UsedGood';
    const CONDITION_USED_ACCEPTABLE        = 'UsedAcceptable';
    const CONDITION_COLLECTIBLE_LIKE_NEW   = 'CollectibleLikeNew';
    const CONDITION_COLLECTIBLE_VERY_GOOD  = 'CollectibleVeryGood';
    const CONDITION_COLLECTIBLE_GOOD       = 'CollectibleGood';
    const CONDITION_COLLECTIBLE_ACCEPTABLE = 'CollectibleAcceptable';
    const CONDITION_REFURBISHED            = 'Refurbished';
    const CONDITION_CLUB                   = 'Club';

    const CONDITION_NOTE_MODE_NONE             = 3;
    const CONDITION_NOTE_MODE_CUSTOM_VALUE     = 1;

    const IMAGE_MAIN_MODE_NONE           = 0;
    const IMAGE_MAIN_MODE_PRODUCT        = 1;
    const IMAGE_MAIN_MODE_ATTRIBUTE      = 2;

    const GALLERY_IMAGES_MODE_NONE       = 0;
    const GALLERY_IMAGES_MODE_PRODUCT    = 1;
    const GALLERY_IMAGES_MODE_ATTRIBUTE  = 2;

    const GALLERY_IMAGES_COUNT_MAX       = 5;

    const HANDLING_TIME_MODE_NONE             = 3;
    const HANDLING_TIME_MODE_RECOMMENDED      = 1;
    const HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE = 2;

    const RESTOCK_DATE_MODE_NONE              = 1;
    const RESTOCK_DATE_MODE_CUSTOM_VALUE      = 2;
    const RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE  = 3;

    const GIFT_WRAP_MODE_NO = 0;
    const GIFT_WRAP_MODE_YES = 1;
    const GIFT_WRAP_MODE_ATTRIBUTE = 2;

    const GIFT_MESSAGE_MODE_NO = 0;
    const GIFT_MESSAGE_MODE_YES = 1;
    const GIFT_MESSAGE_MODE_ATTRIBUTE = 2;

    const ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_NO  = 0;
    const ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_YES = 1;

    //########################################

    /**
     * @var \Ess\M2ePro\Model\Template\SellingFormat
     */
    private $sellingFormatTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Template\Synchronization
     */
    private $synchronizationTemplateModel = NULL;

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Source[] */
    private $listingSourceModels = array();

    protected $currencyModel;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Currency $currencyModel,
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
        $this->currencyModel = $currencyModel;
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
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Listing');
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('listing');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        $this->getHelper('Data\Cache\Permanent')->removeTagValues('listing');

        $temp = parent::delete();
        $temp && $this->sellingFormatTemplateModel = NULL;
        $temp && $this->synchronizationTemplateModel = NULL;
        return $temp;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->listingSourceModels[$productId])) {
            return $this->listingSourceModels[$productId];
        }

        $this->listingSourceModels[$productId] = $this->modelFactory->getObject('Amazon\Listing\Source');
        $this->listingSourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->listingSourceModels[$productId]->setListing($this->getParentObject());

        return $this->listingSourceModels[$productId];
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        return $this->getParentObject()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account
     */
    public function getAmazonAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        return $this->getParentObject()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Marketplace
     */
    public function getAmazonMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        if (is_null($this->sellingFormatTemplateModel)) {
            $this->sellingFormatTemplateModel = $this->parentFactory->getCachedObjectLoaded(
                $this->getComponentMode(), 'Template\SellingFormat',$this->getData('template_selling_format_id')
            );
        }

        return $this->sellingFormatTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\SellingFormat $instance
     */
    public function setSellingFormatTemplate(\Ess\M2ePro\Model\Template\SellingFormat $instance)
    {
         $this->sellingFormatTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Synchronization
     */
    public function getSynchronizationTemplate()
    {
        if (is_null($this->synchronizationTemplateModel)) {
            $this->synchronizationTemplateModel = $this->parentFactory->getCachedObjectLoaded(
                $this->getComponentMode(), 'Template\Synchronization', $this->getData('template_synchronization_id')
            );
        }

        return $this->synchronizationTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\Synchronization $instance
     */
    public function setSynchronizationTemplate(\Ess\M2ePro\Model\Template\Synchronization $instance)
    {
         $this->synchronizationTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\SellingFormat
     */
    public function getAmazonSellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Synchronization
     */
    public function getAmazonSynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array
     */
    public function getProducts($asObjects = false, array $filters = array())
    {
        return $this->getParentObject()->getProducts($asObjects,$filters);
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return mixed
     */
    public function getCategories($asObjects = false, array $filters = array())
    {
        return $this->getParentObject()->getCategories($asObjects,$filters);
    }

    //########################################

    /**
     * @return int
     */
    public function getAutoGlobalAddingDescriptionTemplateId()
    {
        return (int)$this->getData('auto_global_adding_description_template_id');
    }

    /**
     * @return int
     */
    public function getAutoWebsiteAddingDescriptionTemplateId()
    {
        return (int)$this->getData('auto_website_adding_description_template_id');
    }

    //########################################

    /**
     * @return int
     */
    public function getSkuMode()
    {
        return (int)$this->getData('sku_mode');
    }

    /**
     * @return bool
     */
    public function isSkuProductIdMode()
    {
        return $this->getSkuMode() == self::SKU_MODE_PRODUCT_ID;
    }

    /**
     * @return bool
     */
    public function isSkuDefaultMode()
    {
        return $this->getSkuMode() == self::SKU_MODE_DEFAULT;
    }

    /**
     * @return bool
     */
    public function isSkuAttributeMode()
    {
        return $this->getSkuMode() == self::SKU_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getSkuSource()
    {
        return array(
            'mode'      => $this->getSkuMode(),
            'attribute' => $this->getData('sku_custom_attribute')
        );
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getSkuModificationMode()
    {
        return (int)$this->getData('sku_modification_mode');
    }

    /**
     * @return bool
     */
    public function isSkuModificationModeNone()
    {
        return $this->getSkuModificationMode() == self::SKU_MODIFICATION_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isSkuModificationModePrefix()
    {
        return $this->getSkuModificationMode() == self::SKU_MODIFICATION_MODE_PREFIX;
    }

    /**
     * @return bool
     */
    public function isSkuModificationModePostfix()
    {
        return $this->getSkuModificationMode() == self::SKU_MODIFICATION_MODE_POSTFIX;
    }

    /**
     * @return bool
     */
    public function isSkuModificationModeTemplate()
    {
        return $this->getSkuModificationMode() == self::SKU_MODIFICATION_MODE_TEMPLATE;
    }

    /**
     * @return array
     */
    public function getSkuModificationSource()
    {
        return array(
            'mode'  => $this->getSkuModificationMode(),
            'value' => $this->getData('sku_modification_custom_value')
        );
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isGenerateSkuModeNo()
    {
        return (int)$this->getData('generate_sku_mode') == self::GENERATE_SKU_MODE_NO;
    }

    /**
     * @return bool
     */
    public function isGenerateSkuModeYes()
    {
        return (int)$this->getData('generate_sku_mode') == self::GENERATE_SKU_MODE_YES;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getGeneralIdMode()
    {
        return (int)$this->getData('general_id_mode');
    }

    /**
     * @return bool
     */
    public function isGeneralIdNotSetMode()
    {
        return $this->getGeneralIdMode() == self::GENERAL_ID_MODE_NOT_SET;
    }

    /**
     * @return bool
     */
    public function isGeneralIdAttributeMode()
    {
        return $this->getGeneralIdMode() == self::GENERAL_ID_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getGeneralIdSource()
    {
        return array(
            'mode'      => $this->getGeneralIdMode(),
            'attribute' => $this->getData('general_id_custom_attribute')
        );
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getWorldwideIdMode()
    {
        return (int)$this->getData('worldwide_id_mode');
    }

    /**
     * @return bool
     */
    public function isWorldwideIdNotSetMode()
    {
        return $this->getWorldwideIdMode() == self::WORLDWIDE_ID_MODE_NOT_SET;
    }

    /**
     * @return bool
     */
    public function isWorldwideIdAttributeMode()
    {
        return $this->getWorldwideIdMode() == self::WORLDWIDE_ID_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getWorldwideIdSource()
    {
        return array(
            'mode'      => $this->getWorldwideIdMode(),
            'attribute' => $this->getData('worldwide_id_custom_attribute')
        );
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getSearchByMagentoTitleMode()
    {
        return (int)$this->getData('search_by_magento_title_mode');
    }

    /**
     * @return bool
     */
    public function isSearchByMagentoTitleModeEnabled()
    {
        return $this->getSearchByMagentoTitleMode() == self::SEARCH_BY_MAGENTO_TITLE_MODE_YES;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getConditionMode()
    {
        return (int)$this->getData('condition_mode');
    }

    /**
     * @return bool
     */
    public function isConditionDefaultMode()
    {
        return $this->getConditionMode() == self::CONDITION_MODE_DEFAULT;
    }

    /**
     * @return bool
     */
    public function isConditionAttributeMode()
    {
        return $this->getConditionMode() == self::CONDITION_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getConditionSource()
    {
        return array(
            'mode'      => $this->getConditionMode(),
            'value'     => $this->getData('condition_value'),
            'attribute' => $this->getData('condition_custom_attribute')
        );
    }

    public function getConditionValues()
    {
        $temp = $this->getData('cache_condition_values');

        if (!empty($temp)) {
            return $temp;
        }

        $reflectionClass = new \ReflectionClass (__CLASS__);
        $tempConstants = $reflectionClass->getConstants();

        $values = array();
        foreach ($tempConstants as $key => $value) {
            $prefixKey = strtolower(substr($key,0,14));
            if (substr($prefixKey,0,10) != 'condition_' ||
                in_array($prefixKey,array('condition_mode','condition_note'))) {
                continue;
            }
            $values[] = $value;
        }

        $this->setData('cache_condition_values',$values);

        return $values;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getConditionNoteMode()
    {
        return (int)$this->getData('condition_note_mode');
    }

    /**
     * @return bool
     */
    public function isConditionNoteNoneMode()
    {
        return $this->getConditionNoteMode() == self::CONDITION_NOTE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isConditionNoteValueMode()
    {
        return $this->getConditionNoteMode() == self::CONDITION_NOTE_MODE_CUSTOM_VALUE;
    }

    /**
     * @return array
     */
    public function getConditionNoteSource()
    {
        return array(
            'mode'      => $this->getConditionNoteMode(),
            'value'     => $this->getData('condition_note_value')
        );
    }

    /**
     * @return array
     */
    public function getConditionNoteAttributes()
    {
        $attributes = array();
        $src = $this->getConditionNoteSource();

        if ($src['mode'] == self::CONDITION_NOTE_MODE_CUSTOM_VALUE) {
            $match = array();
            preg_match_all('/#([a-zA-Z_0-9]+?)#/', $src['value'], $match);
            $match && $attributes = $match[1];
        }

        return $attributes;
    }

    // ---------------------------------------

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
            'mode'      => $this->getGalleryImagesMode(),
            'limit'     => $this->getData('gallery_images_limit'),
            'attribute' => $this->getData('gallery_images_attribute')
        );
    }

    /**
     * @return array
     */
    public function getGalleryImagesAttributes()
    {
        $attributes = array();
        $src = $this->getGalleryImagesSource();

        if ($src['mode'] == self::GALLERY_IMAGES_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getHandlingTimeMode()
    {
        return (int)$this->getData('handling_time_mode');
    }

    /**
     * @return bool
     */
    public function isHandlingTimeNoneMode()
    {
        return $this->getHandlingTimeMode() == self::HANDLING_TIME_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isHandlingTimeRecommendedMode()
    {
        return $this->getHandlingTimeMode() == self::HANDLING_TIME_MODE_RECOMMENDED;
    }

    /**
     * @return bool
     */
    public function isHandlingTimeAttributeMode()
    {
        return $this->getHandlingTimeMode() == self::HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getHandlingTimeSource()
    {
        return array(
            'mode'      => $this->getHandlingTimeMode(),
            'value'     => (int)$this->getData('handling_time_value'),
            'attribute' => $this->getData('handling_time_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getHandlingTimeAttributes()
    {
        $attributes = array();
        $src = $this->getHandlingTimeSource();

        if ($src['mode'] == self::HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getRestockDateMode()
    {
        return (int)$this->getData('restock_date_mode');
    }

    /**
     * @return bool
     */
    public function isRestockDateNoneMode()
    {
        return $this->getRestockDateMode() == self::RESTOCK_DATE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isRestockDateValueMode()
    {
        return $this->getRestockDateMode() == self::RESTOCK_DATE_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isRestockDateAttributeMode()
    {
        return $this->getRestockDateMode() == self::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getRestockDateSource()
    {
        return array(
            'mode'      => $this->getRestockDateMode(),
            'value'     => $this->getData('restock_date_value'),
            'attribute' => $this->getData('restock_date_custom_attribute')
        );
    }

    /**
     * @return array
     */
    public function getRestockDateAttributes()
    {
        $attributes = array();
        $src = $this->getRestockDateSource();

        if ($src['mode'] == self::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    public function getGiftWrapMode()
    {
        return $this->getData('gift_wrap_mode');
    }

    /**
     * @return bool
     */
    public function isGiftWrapModeYes()
    {
        return $this->getGiftWrapMode() == self::GIFT_WRAP_MODE_YES;
    }

    /**
     * @return bool
     */
    public function isGiftWrapModeNo()
    {
        return $this->getGiftWrapMode() == self::GIFT_WRAP_MODE_NO;
    }

    /**
     * @return bool
     */
    public function isGiftWrapModeAttribute()
    {
        return $this->getGiftWrapMode() == self::GIFT_WRAP_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getGiftWrapSource()
    {
        return array(
            'mode' => $this->getGiftWrapMode(),
            'attribute' => $this->getData('gift_wrap_attribute')
        );
    }

    /**
     * @return array
     */
    public function getGiftWrapAttributes()
    {
        $attributes = array();
        $src = $this->getGiftWrapSource();

        if ($src['mode'] == self::GIFT_WRAP_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    public function getGiftMessageMode()
    {
        return $this->getData('gift_message_mode');
    }

    /**
     * @return bool
     */
    public function isGiftMessageModeYes()
    {
        return $this->getGiftMessageMode() == self::GIFT_MESSAGE_MODE_YES;
    }

    /**
     * @return bool
     */
    public function isGiftMessageModeNo()
    {
        return $this->getGiftMessageMode() == self::GIFT_MESSAGE_MODE_NO;
    }

    /**
     * @return bool
     */
    public function isGiftMessageModeAttribute()
    {
        return $this->getGiftMessageMode() == self::GIFT_MESSAGE_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getGiftMessageSource()
    {
        return array(
            'mode' => $this->getGiftMessageMode(),
            'attribute' => $this->getData('gift_message_attribute')
        );
    }

    /**
     * @return array
     */
    public function getGiftMessageAttributes()
    {
        $attributes = array();
        $src = $this->getGiftMessageSource();

        if ($src['mode'] == self::GIFT_MESSAGE_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Other $listingOtherProduct
     * @param int $initiator
     * @param bool $checkingMode
     * @param bool $checkHasProduct
     * @return bool|\Ess\M2ePro\Model\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addProductFromOther(\Ess\M2ePro\Model\Listing\Other $listingOtherProduct,
                                        $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
                                        $checkingMode = false,
                                        $checkHasProduct = true)
    {
        if (!$listingOtherProduct->getProductId()) {
            return false;
        }

        $productId = $listingOtherProduct->getProductId();
        $result = $this->getParentObject()->addProduct($productId, $initiator, $checkingMode, $checkHasProduct);

        if ($checkingMode) {
            return $result;
        }

        if (!($result instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return false;
        }

        $listingProduct = $result;

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        if ($variationManager->isRelationParentType()) {
            $variationManager->switchModeToAnother();
        }

        $amazonListingProduct->getAmazonItem()
            ->setData('store_id', $this->getParentObject()->getStoreId())
            ->save();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Other $amazonListingOther */
        $amazonListingOther = $listingOtherProduct->getChildObject();

        $dataForUpdate = array(
            'general_id'           => $amazonListingOther->getGeneralId(),
            'sku'                  => $amazonListingOther->getSku(),
            'online_regular_price' => $amazonListingOther->getOnlinePrice(),
            'online_qty'           => $amazonListingOther->getOnlineQty(),
            'is_repricing'         => (int)$amazonListingOther->isRepricing(),
            'is_afn_channel'       => (int)$amazonListingOther->isAfnChannel(),
            'is_isbn_general_id'   => (int)$amazonListingOther->isIsbnGeneralId(),
            'status'               => $listingOtherProduct->getStatus(),
            'status_changer'       => $listingOtherProduct->getStatusChanger()
        );

        $listingProduct->addData($dataForUpdate);
        $amazonListingProduct->addData($dataForUpdate);
        $listingProduct->save();

        if ($amazonListingOther->isRepricing()) {
            $listingProductRepricing = $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing');
            $listingProductRepricing->setData(array(
                'listing_product_id' => $listingProduct->getId(),
                'is_online_disabled' => $amazonListingOther->isRepricingDisabled(),
                'update_date'        => $this->getHelper('Data')->getCurrentGmtDate(),
                'create_date'        => $this->getHelper('Data')->getCurrentGmtDate(),
            ));
            $listingProductRepricing->save();
        }

        return $listingProduct;
    }

    //########################################

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        return array_unique(array_merge(
            $this->getConditionNoteAttributes(),
            $this->getImageMainAttributes(),
            $this->getGalleryImagesAttributes(),
            $this->getGiftWrapAttributes(),
            $this->getGiftMessageAttributes(),
            $this->getHandlingTimeAttributes(),
            $this->getRestockDateAttributes(),
            $this->getSellingFormatTemplate()->getTrackingAttributes()
        ));
    }

    //########################################

    /**
     * @param bool $asArrays
     * @param string|array $columns
     * @param bool $onlyPhysicalUnits
     * @return array
     */
    public function getAffectedListingsProducts($asArrays = true, $columns = '*', $onlyPhysicalUnits = false)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->parentFactory->getObject($this->getComponentMode(), 'Listing\Product')
            ->getCollection();
        $listingProductCollection->addFieldToFilter('listing_id', $this->getId());

        if ($onlyPhysicalUnits) {
            $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        }

        if (is_array($columns) && !empty($columns)) {
            $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $listingProductCollection->getSelect()->columns($columns);
        }

        return $asArrays ? (array)$listingProductCollection->getData() : (array)$listingProductCollection->getItems();
    }

    public function setSynchStatusNeed($newData, $oldData)
    {
        $listingsProducts = $this->getAffectedListingsProducts(
            true, array('id', 'synch_status', 'synch_reasons'), true
        );
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