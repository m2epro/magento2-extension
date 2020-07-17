<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Description;

/**
 * Class \Ess\M2ePro\Model\Amazon\Template\Description\Diff
 */
class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    //########################################

    public function isDifferent()
    {
        return $this->isDetailsDifferent() ||
               $this->isImagesDifferent();
    }

    //########################################

    public function isDetailsDifferent()
    {
        $mainKeys = [
            'is_new_asin_accepted',
            'worldwide_id_mode',
            'category_path',
            'browsenode_id',
            'product_data_nick',
            'registered_parameter',
            'specifics'
        ];

        if ($this->isSettingsDifferent($mainKeys)) {
            return true;
        }

        $definitionKeys = [
            'title_mode',
            'title_template',
            'brand_custom_value',
            'brand_custom_attribute',
            'item_package_quantity_mode',
            'item_package_quantity_custom_value',
            'item_package_quantity_custom_attribute',
            'number_of_items_mode',
            'number_of_items_custom_value',
            'number_of_items_custom_attribute',
            'description_mode',
            'description_template',
            'target_audience_mode',
            'target_audience',
            'bullet_points_mode',
            'bullet_points',
            'search_terms_mode',
            'search_terms',
            'manufacturer_mode',
            'manufacturer_custom_value',
            'manufacturer_custom_attribute',
            'manufacturer_part_number_mode',
            'manufacturer_part_number_custom_value',
            'manufacturer_part_number_custom_attribute',
            'msrp_rrp_mode',
            'msrp_rrp_custom_attribute',
            'item_dimensions_volume_mode',
            'item_dimensions_volume_length_custom_value',
            'item_dimensions_volume_width_custom_value',
            'item_dimensions_volume_height_custom_value',
            'item_dimensions_volume_length_custom_attribute',
            'item_dimensions_volume_width_custom_attribute',
            'item_dimensions_volume_height_custom_attribute',
            'item_dimensions_volume_unit_of_measure_mode',
            'item_dimensions_volume_unit_of_measure_custom_value',
            'item_dimensions_volume_unit_of_measure_custom_attribute',
            'item_dimensions_weight_mode',
            'item_dimensions_weight_custom_value',
            'item_dimensions_weight_custom_attribute',
            'item_dimensions_weight_unit_of_measure_mode',
            'item_dimensions_weight_unit_of_measure_custom_value',
            'item_dimensions_weight_unit_of_measure_custom_attribute',
            'package_dimensions_volume_mode',
            'package_dimensions_volume_length_custom_value',
            'package_dimensions_volume_width_custom_value',
            'package_dimensions_volume_height_custom_value',
            'package_dimensions_volume_length_custom_attribute',
            'package_dimensions_volume_width_custom_attribute',
            'package_dimensions_volume_height_custom_attribute',
            'package_dimensions_volume_unit_of_measure_mode',
            'package_dimensions_volume_unit_of_measure_custom_value',
            'package_dimensions_volume_unit_of_measure_custom_attribute',
            'package_weight_mode',
            'package_weight_custom_value',
            'package_weight_custom_attribute',
            'package_weight_unit_of_measure_mode',
            'package_weight_unit_of_measure_custom_value',
            'package_weight_unit_of_measure_custom_attribute',
            'shipping_weight_mode',
            'shipping_weight_custom_value',
            'shipping_weight_custom_attribute',
            'shipping_weight_unit_of_measure_mode',
            'shipping_weight_unit_of_measure_custom_value',
            'shipping_weight_unit_of_measure_custom_attribute',
        ];

        if ($this->isSettingsDifferent($definitionKeys, 'definition')) {
            return true;
        }

        return false;
    }

    public function isImagesDifferent()
    {
        $definitionKeys = [
            'image_main_mode',
            'image_main_attribute',
            'image_variation_difference_mode',
            'image_variation_difference_attribute',
            'gallery_images_mode',
            'gallery_images_attribute',
            'gallery_images_limit',
        ];

        return $this->isSettingsDifferent($definitionKeys, 'definition');
    }

    //########################################
}
