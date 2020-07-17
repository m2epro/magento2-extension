<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Description\Definition;

use \Ess\M2ePro\Model\Amazon\Template\Description\Definition as Definition;

/**
 * Class Ess\M2ePro\Model\Amazon\Template\Description\Definition\Builder
 */
class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    private $templateDescriptionId;

    //########################################

    public function setTemplateDescriptionId($templateDescriptionId)
    {
        $this->templateDescriptionId = $templateDescriptionId;
    }

    public function getTemplateDescriptionId()
    {
        if (empty($this->templateDescriptionId)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('descriptionTemplateId not set');
        }

        return $this->templateDescriptionId;
    }

    //########################################

    protected function prepareData()
    {
        $data = [];

        $keys = array_keys($this->getDefaultData());

        foreach ($keys as $key) {
            isset($this->rawData[$key]) && $data[$key] = $this->rawData[$key];
        }

        $data['template_description_id'] = $this->getTemplateDescriptionId();

        $data['target_audience'] = $this->getHelper('Data')->jsonEncode(array_filter($data['target_audience']));
        $data['search_terms'] = $this->getHelper('Data')->jsonEncode(array_filter($data['search_terms']));
        $data['bullet_points'] = $this->getHelper('Data')->jsonEncode(array_filter($data['bullet_points']));

        return $data;
    }

    public function getDefaultData()
    {
        return [
            'title_mode'     => Definition::TITLE_MODE_PRODUCT,
            'title_template' => '',

            'brand_mode'             => Definition::BRAND_MODE_NONE,
            'brand_custom_value'     => '',
            'brand_custom_attribute' => '',

            'manufacturer_mode'             => Definition::MANUFACTURER_MODE_NONE,
            'manufacturer_custom_value'     => '',
            'manufacturer_custom_attribute' => '',

            'manufacturer_part_number_mode'             => Definition::MANUFACTURER_PART_NUMBER_MODE_NONE,
            'manufacturer_part_number_custom_value'     => '',
            'manufacturer_part_number_custom_attribute' => '',

            // ---

            'item_package_quantity_mode'             => Definition::ITEM_PACKAGE_QUANTITY_MODE_NONE,
            'item_package_quantity_custom_value'     => '',
            'item_package_quantity_custom_attribute' => '',

            'number_of_items_mode'             => Definition::NUMBER_OF_ITEMS_MODE_NONE,
            'number_of_items_custom_value'     => '',
            'number_of_items_custom_attribute' => '',

            // ---

            'msrp_rrp_mode'             => Definition::MSRP_RRP_MODE_NONE,
            'msrp_rrp_custom_attribute' => '',

            // ---

            'item_dimensions_volume_mode'                    => Definition::DIMENSION_VOLUME_MODE_NONE,
            'item_dimensions_volume_length_custom_value'     => '',
            'item_dimensions_volume_width_custom_value'      => '',
            'item_dimensions_volume_height_custom_value'     => '',
            'item_dimensions_volume_length_custom_attribute' => '',
            'item_dimensions_volume_width_custom_attribute'  => '',
            'item_dimensions_volume_height_custom_attribute' => '',

            'item_dimensions_volume_unit_of_measure_mode'             =>
                Definition::DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
            'item_dimensions_volume_unit_of_measure_custom_value'     => '',
            'item_dimensions_volume_unit_of_measure_custom_attribute' => '',

            'item_dimensions_weight_mode'             => Definition::WEIGHT_MODE_NONE,
            'item_dimensions_weight_custom_value'     => '',
            'item_dimensions_weight_custom_attribute' => '',

            'item_dimensions_weight_unit_of_measure_mode'             =>
                Definition::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
            'item_dimensions_weight_unit_of_measure_custom_value'     => '',
            'item_dimensions_weight_unit_of_measure_custom_attribute' => '',

            // ---

            'package_dimensions_volume_mode'                    => Definition::DIMENSION_VOLUME_MODE_NONE,
            'package_dimensions_volume_length_custom_value'     => '',
            'package_dimensions_volume_width_custom_value'      => '',
            'package_dimensions_volume_height_custom_value'     => '',
            'package_dimensions_volume_length_custom_attribute' => '',
            'package_dimensions_volume_width_custom_attribute'  => '',
            'package_dimensions_volume_height_custom_attribute' => '',

            'package_dimensions_volume_unit_of_measure_mode'             =>
                Definition::DIMENSION_VOLUME_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
            'package_dimensions_volume_unit_of_measure_custom_value'     => '',
            'package_dimensions_volume_unit_of_measure_custom_attribute' => '',

            // ---

            'package_weight_mode'             => Definition::WEIGHT_MODE_NONE,
            'package_weight_custom_value'     => '',
            'package_weight_custom_attribute' => '',

            'package_weight_unit_of_measure_mode'             => Definition::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
            'package_weight_unit_of_measure_custom_value'     => '',
            'package_weight_unit_of_measure_custom_attribute' => '',

            'shipping_weight_mode'             => Definition::WEIGHT_MODE_NONE,
            'shipping_weight_custom_value'     => '',
            'shipping_weight_custom_attribute' => '',

            'shipping_weight_unit_of_measure_mode'             => Definition::WEIGHT_UNIT_OF_MEASURE_MODE_CUSTOM_VALUE,
            'shipping_weight_unit_of_measure_custom_value'     => '',
            'shipping_weight_unit_of_measure_custom_attribute' => '',

            // ---

            'target_audience_mode' => Definition::TARGET_AUDIENCE_MODE_NONE,
            'target_audience'      => $this->getHelper('Data')->jsonEncode([]),

            'search_terms_mode' => Definition::SEARCH_TERMS_MODE_NONE,
            'search_terms'      => $this->getHelper('Data')->jsonEncode([]),

            'image_main_mode'      => Definition::IMAGE_MAIN_MODE_PRODUCT,
            'image_main_attribute' => '',

            'image_variation_difference_mode'      => Definition::IMAGE_VARIATION_DIFFERENCE_MODE_NONE,
            'image_variation_difference_attribute' => '',

            'gallery_images_mode'      => Definition::GALLERY_IMAGES_MODE_NONE,
            'gallery_images_limit'     => 1,
            'gallery_images_attribute' => '',

            'bullet_points_mode' => Definition::BULLET_POINTS_MODE_NONE,
            'bullet_points'      => $this->getHelper('Data')->jsonEncode([]),

            'description_mode'     => Definition::DESCRIPTION_MODE_NONE,
            'description_template' => '',
        ];
    }

    //########################################
}
