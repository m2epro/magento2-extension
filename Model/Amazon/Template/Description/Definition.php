<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Model\Amazon\Template\Description\Definition
 */
class Definition extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    const TITLE_MODE_CUSTOM  = 1;
    const TITLE_MODE_PRODUCT = 2;

    const BRAND_MODE_NONE             = 0;
    const BRAND_MODE_CUSTOM_VALUE     = 1;
    const BRAND_MODE_CUSTOM_ATTRIBUTE = 2;

    const ITEM_PACKAGE_QUANTITY_MODE_NONE             = 0;
    const ITEM_PACKAGE_QUANTITY_MODE_CUSTOM_VALUE     = 1;
    const ITEM_PACKAGE_QUANTITY_MODE_CUSTOM_ATTRIBUTE = 2;

    const NUMBER_OF_ITEMS_MODE_NONE             = 0;
    const NUMBER_OF_ITEMS_MODE_CUSTOM_VALUE     = 1;
    const NUMBER_OF_ITEMS_MODE_CUSTOM_ATTRIBUTE = 2;

    const DESCRIPTION_MODE_NONE     = 0;
    const DESCRIPTION_MODE_PRODUCT  = 1;
    const DESCRIPTION_MODE_SHORT    = 2;
    const DESCRIPTION_MODE_CUSTOM   = 3;

    const TARGET_AUDIENCE_MODE_NONE   = 0;
    const TARGET_AUDIENCE_MODE_CUSTOM = 1;

    const BULLET_POINTS_MODE_NONE   = 0;
    const BULLET_POINTS_MODE_CUSTOM = 1;

    const SEARCH_TERMS_MODE_NONE   = 0;
    const SEARCH_TERMS_MODE_CUSTOM = 1;

    const MANUFACTURER_MODE_NONE             = 0;
    const MANUFACTURER_MODE_CUSTOM_VALUE     = 1;
    const MANUFACTURER_MODE_CUSTOM_ATTRIBUTE = 2;

    const MANUFACTURER_PART_NUMBER_MODE_NONE             = 0;
    const MANUFACTURER_PART_NUMBER_MODE_CUSTOM_VALUE     = 1;
    const MANUFACTURER_PART_NUMBER_MODE_CUSTOM_ATTRIBUTE = 2;

    const MSRP_RRP_MODE_NONE             = 0;
    const MSRP_RRP_MODE_CUSTOM_ATTRIBUTE = 1;

    const DIMENSION_VOLUME_MODE_NONE             = 0;
    const DIMENSION_VOLUME_MODE_CUSTOM_VALUE     = 1;
    const DIMENSION_VOLUME_MODE_CUSTOM_ATTRIBUTE = 2;

    const DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE     = 1;
    const DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE = 2;

    const WEIGHT_MODE_NONE             = 0;
    const WEIGHT_MODE_CUSTOM_VALUE     = 1;
    const WEIGHT_MODE_CUSTOM_ATTRIBUTE = 2;

    const WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE     = 1;
    const WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE = 2;

    const IMAGE_MAIN_MODE_NONE       = 0;
    const IMAGE_MAIN_MODE_PRODUCT    = 1;
    const IMAGE_MAIN_MODE_ATTRIBUTE  = 2;

    const IMAGE_VARIATION_DIFFERENCE_MODE_NONE      = 0;
    const IMAGE_VARIATION_DIFFERENCE_MODE_PRODUCT   = 1;
    const IMAGE_VARIATION_DIFFERENCE_MODE_ATTRIBUTE = 2;

    const GALLERY_IMAGES_MODE_NONE      = 0;
    const GALLERY_IMAGES_MODE_PRODUCT   = 1;
    const GALLERY_IMAGES_MODE_ATTRIBUTE = 2;

    /**
     * @var \Ess\M2ePro\Model\Template\Description
     */
    private $descriptionTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\Description\Definition\Source[]
     */
    private $descriptionDefinitionSourceModels = [];

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        parent::__construct(
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
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Template\Description\Definition');
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_description_definition');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->descriptionTemplateModel = null;
        $temp && $this->descriptionDefinitionSourceModels = [];

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_description_definition');

        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     * @throws \Exception
     */
    public function getDescriptionTemplate()
    {
        if ($this->descriptionTemplateModel === null) {
            $this->descriptionTemplateModel = $this->amazonFactory->getCachedObjectLoaded(
                'Template\Description',
                $this->getId()
            );
        }

        return $this->descriptionTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\Description $instance
     */
    public function setDescriptionTemplate(\Ess\M2ePro\Model\Template\Description $instance)
    {
        $this->descriptionTemplateModel = $instance;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Description
     * @throws \Exception
     */
    public function getAmazonDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Amazon\Template\Description\Definition\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->descriptionDefinitionSourceModels[$productId])) {
            return $this->descriptionDefinitionSourceModels[$productId];
        }

        $this->descriptionDefinitionSourceModels[$productId] = $this->modelFactory->getObject(
            'Amazon_Template_Description_Definition_Source'
        );
        $this->descriptionDefinitionSourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->descriptionDefinitionSourceModels[$productId]->setDescriptionDefinitionTemplate($this);

        return $this->descriptionDefinitionSourceModels[$productId];
    }

    //########################################

    /**
     * @return int
     */
    public function getTemplateDescriptionId()
    {
        return (int)$this->getData('template_description_id');
    }

    // ---------------------------------------

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
            'mode'     => $this->getTitleMode(),
            'template' => $this->getData('title_template')
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
    public function getBrandMode()
    {
        return (int)$this->getData('brand_mode');
    }

    /**
     * @return bool
     */
    public function isBrandModeNone()
    {
        return $this->getBrandMode() == self::BRAND_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isBrandModeCustomValue()
    {
        return $this->getBrandMode() == self::BRAND_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isBrandModeCustomAttribute()
    {
        return $this->getBrandMode() == self::BRAND_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getBrandSource()
    {
        return [
            'mode'             => $this->getBrandMode(),
            'custom_value'     => $this->getData('brand_custom_value'),
            'custom_attribute' => $this->getData('brand_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getBrandAttributes()
    {
        $attributes = [];
        $src = $this->getBrandSource();

        if ($src['mode'] == self::BRAND_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getItemPackageQuantityMode()
    {
        return (int)$this->getData('item_package_quantity_mode');
    }

    public function getItemPackageQuantityCustomValue()
    {
        return $this->getData('item_package_quantity_custom_value');
    }

    public function getItemPackageQuantityCustomAttribute()
    {
        return $this->getData('item_package_quantity_custom_attribute');
    }

    /**
     * @return bool
     */
    public function isItemPackageQuantityModeNone()
    {
        return $this->getItemPackageQuantityMode() == self::ITEM_PACKAGE_QUANTITY_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isItemPackageQuantityModeCustomValue()
    {
        return $this->getItemPackageQuantityMode() == self::ITEM_PACKAGE_QUANTITY_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isItemPackageQuantityModeCustomAttribute()
    {
        return $this->getItemPackageQuantityMode() == self::ITEM_PACKAGE_QUANTITY_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getItemPackageQuantitySource()
    {
        return [
            'mode'      => $this->getItemPackageQuantityMode(),
            'value'     => $this->getItemPackageQuantityCustomValue(),
            'attribute' => $this->getItemPackageQuantityCustomAttribute()
        ];
    }

    /**
     * @return array
     */
    public function getItemPackageQuantityAttributes()
    {
        $attributes = [];
        $src = $this->getItemPackageQuantitySource();

        if ($src['mode'] == self::ITEM_PACKAGE_QUANTITY_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getNumberOfItemsMode()
    {
        return (int)$this->getData('number_of_items_mode');
    }

    /**
     * @return bool
     */
    public function isNumberOfItemsModeNone()
    {
        return $this->getNumberOfItemsMode() == self::NUMBER_OF_ITEMS_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isNumberOfItemsModeCustomValue()
    {
        return $this->getNumberOfItemsMode() == self::NUMBER_OF_ITEMS_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isNumberOfItemsModeCustomAttribute()
    {
        return $this->getNumberOfItemsMode() == self::NUMBER_OF_ITEMS_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getNumberOfItemsSource()
    {
        return [
            'mode'      => $this->getNumberOfItemsMode(),
            'value'     => $this->getData('number_of_items_custom_value'),
            'attribute' => $this->getData('number_of_items_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getNumberOfItemsAttributes()
    {
        $attributes = [];
        $src = $this->getNumberOfItemsSource();

        if ($src['mode'] == self::NUMBER_OF_ITEMS_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
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
    public function isDescriptionModeNone()
    {
        return $this->getDescriptionMode() == self::DESCRIPTION_MODE_NONE;
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
            'mode'     => $this->getDescriptionMode(),
            'template' => $this->getData('description_template')
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
    public function getTargetAudienceMode()
    {
        return (int)$this->getData('target_audience_mode');
    }

    /**
     * @return array
     */
    public function getTargetAudienceTemplate()
    {
        return $this->getData('target_audience') !== null
            ? $this->getHelper('Data')->jsonDecode($this->getData('target_audience'))
            : [];
    }

    /**
     * @return bool
     */
    public function isTargetAudienceModeNone()
    {
        return $this->getTargetAudienceMode() == self::TARGET_AUDIENCE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isTargetAudienceModeCustom()
    {
        return $this->getTargetAudienceMode() == self::TARGET_AUDIENCE_MODE_CUSTOM;
    }

    /**
     * @return array
     */
    public function getTargetAudienceSource()
    {
        return [
            'mode'     => $this->getTargetAudienceMode(),
            'template' => $this->getTargetAudienceTemplate()
        ];
    }

    /**
     * @return array
     */
    public function getTargetAudienceAttributes()
    {
        $src = $this->getTargetAudienceSource();

        if ($src['mode'] == self::TARGET_AUDIENCE_MODE_NONE) {
            return [];
        }

        $attributes = [];

        if ($src['mode'] == self::TARGET_AUDIENCE_MODE_CUSTOM) {
            $match = [];
            $audience = implode(PHP_EOL, $src['template']);
            preg_match_all('/#([a-zA-Z_0-9]+?)#/', $audience, $match);
            $match && $attributes = $match[1];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getBulletPointsMode()
    {
        return (int)$this->getData('bullet_points_mode');
    }

    /**
     * @return array
     */
    public function getBulletPointsTemplate()
    {
        return $this->getData('bullet_points') === null
            ? []
            : $this->getHelper('Data')->jsonDecode($this->getData('bullet_points'));
    }

    /**
     * @return bool
     */
    public function isBulletPointsModeNone()
    {
        return $this->getBulletPointsMode() == self::BULLET_POINTS_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isBulletPointsModeCustom()
    {
        return $this->getBulletPointsMode() == self::BULLET_POINTS_MODE_CUSTOM;
    }

    /**
     * @return array
     */
    public function getBulletPointsSource()
    {
        return [
            'mode'     => $this->getBulletPointsMode(),
            'template' => $this->getBulletPointsTemplate()
        ];
    }

    /**
     * @return array
     */
    public function getBulletPointsAttributes()
    {
        $src = $this->getBulletPointsSource();

        if ($src['mode'] == self::BULLET_POINTS_MODE_NONE) {
            return [];
        }

        $attributes = [];

        if ($src['mode'] == self::BULLET_POINTS_MODE_CUSTOM) {
            $match = [];
            $bullets = implode(PHP_EOL, $src['template']);
            preg_match_all('/#([a-zA-Z_0-9]+?)#/', $bullets, $match);
            $match && $attributes = $match[1];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getSearchTermsMode()
    {
        return (int)$this->getData('search_terms_mode');
    }

    /**
     * @return array
     */
    public function getSearchTermsTemplate()
    {
        return $this->getData('search_terms') === null
            ? []
            : $this->getHelper('Data')->jsonDecode($this->getData('search_terms'));
    }

    /**
     * @return bool
     */
    public function isSearchTermsModeNone()
    {
        return $this->getSearchTermsMode() == self::SEARCH_TERMS_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isSearchTermsModeCustom()
    {
        return $this->getSearchTermsMode() == self::SEARCH_TERMS_MODE_CUSTOM;
    }

    /**
     * @return array
     */
    public function getSearchTermsSource()
    {
        return [
            'mode'     => $this->getSearchTermsMode(),
            'template' => $this->getSearchTermsTemplate()
        ];
    }

    /**
     * @return array
     */
    public function getSearchTermsAttributes()
    {
        $src = $this->getSearchTermsSource();

        if ($src['mode'] == self::SEARCH_TERMS_MODE_NONE) {
            return [];
        }

        $attributes = [];

        if ($src['mode'] == self::SEARCH_TERMS_MODE_CUSTOM) {
            $match = [];
            $searchTerms = implode(PHP_EOL, $src['template']);
            preg_match_all('/#([a-zA-Z_0-9]+?)#/', $searchTerms, $match);
            $match && $attributes = $match[1];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getManufacturerMode()
    {
        return (int)$this->getData('manufacturer_mode');
    }

    /**
     * @return bool
     */
    public function isManufacturerModeNone()
    {
        return $this->getManufacturerMode() == self::MANUFACTURER_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isManufacturerModeCustomValue()
    {
        return $this->getManufacturerMode() == self::MANUFACTURER_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isManufacturerModeCustomAttribute()
    {
        return $this->getManufacturerMode() == self::MANUFACTURER_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getManufacturerSource()
    {
        return [
            'mode'             => $this->getManufacturerMode(),
            'custom_value'     => $this->getData('manufacturer_custom_value'),
            'custom_attribute' => $this->getData('manufacturer_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getManufacturerAttributes()
    {
        $attributes = [];
        $src = $this->getManufacturerSource();

        if ($src['mode'] == self::MANUFACTURER_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getManufacturerPartNumberMode()
    {
        return (int)$this->getData('manufacturer_part_number_mode');
    }

    /**
     * @return bool
     */
    public function isManufacturerPartNumberModeNone()
    {
        return $this->getManufacturerPartNumberMode() == self::MANUFACTURER_PART_NUMBER_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isManufacturerPartNumberModeCustomValue()
    {
        return $this->getManufacturerPartNumberMode() == self::MANUFACTURER_PART_NUMBER_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isManufacturerPartNumberModeCustomAttribute()
    {
        return $this->getManufacturerPartNumberMode() == self::MANUFACTURER_PART_NUMBER_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getManufacturerPartNumberSource()
    {
        return [
            'mode'             => $this->getManufacturerPartNumberMode(),
            'custom_value'     => $this->getData('manufacturer_part_number_custom_value'),
            'custom_attribute' => $this->getData('manufacturer_part_number_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getManufacturerPartNumberAttributes()
    {
        $attributes = [];
        $src = $this->getManufacturerPartNumberSource();

        if ($src['mode'] == self::MANUFACTURER_PART_NUMBER_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getMsrpRrpMode()
    {
        return (int)$this->getData('msrp_rrp_mode');
    }

    /**
     * @return bool
     */
    public function isMsrpRrpModeNone()
    {
        return $this->getMsrpRrpMode() == self::MSRP_RRP_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isMsrpRrpModeCustomAttribute()
    {
        return $this->getMsrpRrpMode() == self::MSRP_RRP_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getMsrpRrpSource()
    {
        return [
            'mode'             => $this->getMsrpRrpMode(),
            'custom_attribute' => $this->getData('msrp_rrp_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getMsrpRrpAttributes()
    {
        $attributes = [];
        $src = $this->getMsrpRrpSource();

        if ($src['mode'] == self::MSRP_RRP_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getItemDimensionsVolumeMode()
    {
        return (int)$this->getData('item_dimensions_volume_mode');
    }

    /**
     * @return bool
     */
    public function isItemDimensionsVolumeModeNone()
    {
        return $this->getItemDimensionsVolumeMode() == self::DIMENSION_VOLUME_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isItemDimensionsVolumeModeCustomValue()
    {
        return $this->getItemDimensionsVolumeMode() == self::DIMENSION_VOLUME_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isItemDimensionsVolumeModeCustomAttribute()
    {
        return $this->getItemDimensionsVolumeMode() == self::DIMENSION_VOLUME_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getItemDimensionsVolumeSource()
    {
        return [
            'mode' => $this->getItemDimensionsVolumeMode(),

            'length_custom_value' => $this->getData('item_dimensions_volume_length_custom_value'),
            'width_custom_value'  => $this->getData('item_dimensions_volume_width_custom_value'),
            'height_custom_value' => $this->getData('item_dimensions_volume_height_custom_value'),

            'length_custom_attribute' => $this->getData('item_dimensions_volume_length_custom_attribute'),
            'width_custom_attribute'  => $this->getData('item_dimensions_volume_width_custom_attribute'),
            'height_custom_attribute' => $this->getData('item_dimensions_volume_height_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getItemDimensionsVolumeAttributes()
    {
        $attributes = [];
        $src = $this->getItemDimensionsVolumeSource();

        if ($src['mode'] == self::WEIGHT_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['length_custom_attribute'];
            $attributes[] = $src['width_custom_attribute'];
            $attributes[] = $src['height_custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getItemDimensionsVolumeUnitOfMeasureMode()
    {
        return (int)$this->getData('item_dimensions_volume_unit_of_measure_mode');
    }

    /**
     * @return bool
     */
    public function isItemDimensionsVolumeUnitOfMeasureModeCustomValue()
    {
        return $this->getItemDimensionsVolumeUnitOfMeasureMode() ==
               self::DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isItemDimensionsVolumeUnitOfMeasureModeCustomAttribute()
    {
        return $this->getItemDimensionsVolumeUnitOfMeasureMode() ==
               self::DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getItemDimensionsVolumeUnitOfMeasureSource()
    {
        return [
            'mode'             => $this->getItemDimensionsVolumeUnitOfMeasureMode(),
            'custom_value'     => $this->getData('item_dimensions_volume_unit_of_measure_custom_value'),
            'custom_attribute' => $this->getData('item_dimensions_volume_unit_of_measure_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getItemDimensionsVolumeUnitOfMeasureAttributes()
    {
        $attributes = [];
        $src = $this->getItemDimensionsVolumeUnitOfMeasureSource();

        if ($src['mode'] == self::DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getItemDimensionsWeightMode()
    {
        return (int)$this->getData('item_dimensions_weight_mode');
    }

    /**
     * @return bool
     */
    public function isItemDimensionsWeightModeNone()
    {
        return $this->getItemDimensionsWeightMode() == self::WEIGHT_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isItemDimensionsWeightModeCustomValue()
    {
        return $this->getItemDimensionsWeightMode() == self::WEIGHT_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isItemDimensionsWeightModeCustomAttribute()
    {
        return $this->getItemDimensionsWeightMode() == self::WEIGHT_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getItemDimensionsWeightSource()
    {
        return [
            'mode'             => $this->getItemDimensionsWeightMode(),
            'custom_value'     => $this->getData('item_dimensions_weight_custom_value'),
            'custom_attribute' => $this->getData('item_dimensions_weight_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getItemDimensionsWeightAttributes()
    {
        $attributes = [];
        $src = $this->getItemDimensionsWeightSource();

        if ($src['mode'] == self::WEIGHT_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getItemDimensionsWeightUnitOfMeasureMode()
    {
        return (int)$this->getData('item_dimensions_weight_unit_of_measure_mode');
    }

    /**
     * @return bool
     */
    public function isItemDimensionsWeightUnitOfMeasureModeCustomValue()
    {
        return $this->getItemDimensionsWeightUnitOfMeasureMode() == self::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isItemDimensionsWeightUnitOfMeasureModeCustomAttribute()
    {
        return $this->getItemDimensionsWeightUnitOfMeasureMode() == self::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getItemDimensionsWeightUnitOfMeasureSource()
    {
        return [
            'mode'             => $this->getItemDimensionsWeightUnitOfMeasureMode(),
            'custom_value'     => $this->getData('item_dimensions_weight_unit_of_measure_custom_value'),
            'custom_attribute' => $this->getData('item_dimensions_weight_unit_of_measure_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getItemDimensionsWeightUnitOfMeasureAttributes()
    {
        $attributes = [];
        $src = $this->getItemDimensionsWeightUnitOfMeasureSource();

        if ($src['mode'] == self::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getPackageDimensionsVolumeMode()
    {
        return (int)$this->getData('package_dimensions_volume_mode');
    }

    /**
     * @return bool
     */
    public function isPackageDimensionsVolumeModeNone()
    {
        return $this->getPackageDimensionsVolumeMode() == self::DIMENSION_VOLUME_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isPackageDimensionsVolumeModeCustomValue()
    {
        return $this->getPackageDimensionsVolumeMode() == self::DIMENSION_VOLUME_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isPackageDimensionsVolumeModeCustomAttribute()
    {
        return $this->getPackageDimensionsVolumeMode() == self::DIMENSION_VOLUME_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getPackageDimensionsVolumeSource()
    {
        return [
            'mode' => $this->getPackageDimensionsVolumeMode(),

            'length_custom_value' => $this->getData('package_dimensions_volume_length_custom_value'),
            'width_custom_value'  => $this->getData('package_dimensions_volume_width_custom_value'),
            'height_custom_value' => $this->getData('package_dimensions_volume_height_custom_value'),

            'length_custom_attribute' => $this->getData('package_dimensions_volume_length_custom_attribute'),
            'width_custom_attribute'  => $this->getData('package_dimensions_volume_width_custom_attribute'),
            'height_custom_attribute' => $this->getData('package_dimensions_volume_height_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getPackageDimensionsVolumeAttributes()
    {
        $attributes = [];
        $src = $this->getPackageDimensionsVolumeSource();

        if ($src['mode'] == self::WEIGHT_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['length_custom_attribute'];
            $attributes[] = $src['width_custom_attribute'];
            $attributes[] = $src['height_custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getPackageDimensionsVolumeUnitOfMeasureMode()
    {
        return (int)$this->getData('package_dimensions_volume_unit_of_measure_mode');
    }

    /**
     * @return bool
     */
    public function isPackageDimensionsVolumeUnitOfMeasureModeCustomValue()
    {
        return $this->getPackageDimensionsVolumeUnitOfMeasureMode() ==
               self::DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isPackageDimensionsVolumeUnitOfMeasureModeCustomAttribute()
    {
        return $this->getPackageDimensionsVolumeUnitOfMeasureMode() ==
               self::DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getPackageDimensionsVolumeUnitOfMeasureSource()
    {
        return [
            'mode'             => $this->getPackageDimensionsVolumeUnitOfMeasureMode(),
            'custom_value'     => $this->getData('package_dimensions_volume_unit_of_measure_custom_value'),
            'custom_attribute' => $this->getData('package_dimensions_volume_unit_of_measure_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getPackageDimensionsVolumeUnitOfMeasureAttributes()
    {
        $attributes = [];
        $src = $this->getPackageDimensionsVolumeUnitOfMeasureSource();

        if ($src['mode'] == self::DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getPackageWeightMode()
    {
        return (int)$this->getData('package_weight_mode');
    }

    /**
     * @return bool
     */
    public function isPackageWeightModeNone()
    {
        return $this->getPackageWeightMode() == self::WEIGHT_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isPackageWeightModeCustomValue()
    {
        return $this->getPackageWeightMode() == self::WEIGHT_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isPackageWeightModeCustomAttribute()
    {
        return $this->getPackageWeightMode() == self::WEIGHT_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getPackageWeightSource()
    {
        return [
            'mode'             => $this->getPackageWeightMode(),
            'custom_value'     => $this->getData('package_weight_custom_value'),
            'custom_attribute' => $this->getData('package_weight_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getPackageWeightAttributes()
    {
        $attributes = [];
        $src = $this->getPackageWeightSource();

        if ($src['mode'] == self::WEIGHT_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getPackageWeightUnitOfMeasureMode()
    {
        return (int)$this->getData('package_weight_unit_of_measure_mode');
    }

    /**
     * @return bool
     */
    public function isPackageWeightUnitOfMeasureModeCustomValue()
    {
        return $this->getPackageWeightUnitOfMeasureMode() == self::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isPackageWeightUnitOfMeasureModeCustomAttribute()
    {
        return $this->getPackageWeightUnitOfMeasureMode() == self::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getPackageWeightUnitOfMeasureSource()
    {
        return [
            'mode'             => $this->getPackageWeightUnitOfMeasureMode(),
            'custom_value'     => $this->getData('package_weight_unit_of_measure_custom_value'),
            'custom_attribute' => $this->getData('package_weight_unit_of_measure_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getPackageWeightUnitOfMeasureAttributes()
    {
        $attributes = [];
        $src = $this->getPackageWeightUnitOfMeasureSource();

        if ($src['mode'] == self::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getShippingWeightMode()
    {
        return (int)$this->getData('shipping_weight_mode');
    }

    /**
     * @return bool
     */
    public function isShippingWeightModeNone()
    {
        return $this->getShippingWeightMode() == self::WEIGHT_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isShippingWeightModeCustomValue()
    {
        return $this->getShippingWeightMode() == self::WEIGHT_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isShippingWeightModeCustomAttribute()
    {
        return $this->getShippingWeightMode() == self::WEIGHT_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getShippingWeightSource()
    {
        return [
            'mode'             => $this->getShippingWeightMode(),
            'custom_value'     => $this->getData('shipping_weight_custom_value'),
            'custom_attribute' => $this->getData('shipping_weight_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getShippingWeightAttributes()
    {
        $attributes = [];
        $src = $this->getShippingWeightSource();

        if ($src['mode'] == self::WEIGHT_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['custom_attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getShippingWeightUnitOfMeasureMode()
    {
        return (int)$this->getData('shipping_weight_unit_of_measure_mode');
    }

    /**
     * @return bool
     */
    public function isShippingWeightUnitOfMeasureModeCustomValue()
    {
        return $this->getShippingWeightUnitOfMeasureMode() == self::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isShippingWeightUnitOfMeasureModeCustomAttribute()
    {
        return $this->getShippingWeightUnitOfMeasureMode() == self::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getShippingWeightUnitOfMeasureSource()
    {
        return [
            'mode'             => $this->getShippingWeightUnitOfMeasureMode(),
            'custom_value'     => $this->getData('shipping_weight_unit_of_measure_custom_value'),
            'custom_attribute' => $this->getData('shipping_weight_unit_of_measure_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getShippingWeightUnitOfMeasureAttributes()
    {
        $attributes = [];
        $src = $this->getShippingWeightUnitOfMeasureSource();

        if ($src['mode'] == self::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['custom_attribute'];
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
        return [
            'mode'     => $this->getImageMainMode(),
            'attribute' => $this->getData('image_main_attribute')
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
    public function getImageVariationDifferenceMode()
    {
        return (int)$this->getData('image_variation_difference_mode');
    }

    /**
     * @return bool
     */
    public function isImageVariationDifferenceModeNone()
    {
        return $this->getImageVariationDifferenceMode() == self::IMAGE_VARIATION_DIFFERENCE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isImageVariationDifferenceModeProduct()
    {
        return $this->getImageVariationDifferenceMode() == self::IMAGE_VARIATION_DIFFERENCE_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isImageVariationDifferenceModeAttribute()
    {
        return $this->getImageVariationDifferenceMode() == self::IMAGE_VARIATION_DIFFERENCE_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getImageVariationDifferenceSource()
    {
        return [
            'mode'     => $this->getImageVariationDifferenceMode(),
            'attribute' => $this->getData('image_variation_difference_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getImageVariationDifferenceAttributes()
    {
        $attributes = [];
        $src = $this->getImageVariationDifferenceSource();

        if ($src['mode'] == self::IMAGE_VARIATION_DIFFERENCE_MODE_PRODUCT) {
            $attributes[] = 'image';
        } elseif ($src['mode'] == self::IMAGE_VARIATION_DIFFERENCE_MODE_ATTRIBUTE) {
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
            'mode'      => $this->getGalleryImagesMode(),
            'attribute' => $this->getData('gallery_images_attribute'),
            'limit'     => $this->getData('gallery_images_limit')
        ];
    }

    /**
     * @return array
     */
    public function getGalleryImagesAttributes()
    {
        $attributes = [];
        $src = $this->getGalleryImagesSource();

        if ($src['mode'] == self::GALLERY_IMAGES_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    //########################################

    /**
     * @return array
     */
    public function getTrackingAttributes()
    {
        return $this->getUsedAttributes();
    }

    /**
     * @return array
     */
    public function getUsedAttributes()
    {
        return array_unique(array_merge(
            $this->getUsedDetailsAttributes(),
            $this->getUsedImagesAttributes()
        ));
    }

    /**
     * @return array
     */
    public function getUsedDetailsAttributes()
    {
        return array_unique(array_merge(

            $this->getTitleAttributes(),
            $this->getBrandAttributes(),
            $this->getNumberOfItemsAttributes(),
            $this->getItemPackageQuantityAttributes(),
            $this->getDescriptionAttributes(),
            $this->getBulletPointsAttributes(),
            $this->getSearchTermsAttributes(),
            $this->getTargetAudienceAttributes(),
            $this->getManufacturerAttributes(),
            $this->getManufacturerPartNumberAttributes(),
            $this->getItemDimensionsVolumeAttributes(),
            $this->getItemDimensionsVolumeUnitOfMeasureAttributes(),
            $this->getItemDimensionsWeightAttributes(),
            $this->getItemDimensionsWeightUnitOfMeasureAttributes(),
            $this->getPackageDimensionsVolumeAttributes(),
            $this->getPackageDimensionsVolumeUnitOfMeasureAttributes(),
            $this->getPackageWeightAttributes(),
            $this->getPackageWeightUnitOfMeasureAttributes(),
            $this->getShippingWeightAttributes(),
            $this->getShippingWeightUnitOfMeasureAttributes()
        ));
    }

    /**
     * @return array
     */
    public function getUsedImagesAttributes()
    {
        return array_unique(array_merge(
            $this->getImageMainAttributes(),
            $this->getImageVariationDifferenceAttributes(),
            $this->getGalleryImagesAttributes()
        ));
    }

    //########################################

    public function getCacheGroupTags()
    {
        return array_merge(parent::getCacheGroupTags(), ['template']);
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
