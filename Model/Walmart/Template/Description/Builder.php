<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Description;

use Ess\M2ePro\Model\Walmart\Template\Description as Description;

/**
 * Class Ess\M2ePro\Model\Walmart\Template\Description\Builder
 */
class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    //########################################

    protected function prepareData()
    {
        $data = [];

        $defaultData = $this->getDefaultData();
        unset($defaultData['id']);
        $keys = array_keys($defaultData);

        foreach ($keys as $key) {
            isset($this->rawData[$key]) && $data[$key] = $this->rawData[$key];
        }

        $data['title'] = strip_tags($data['title']);

        $data['key_features']   = $this->getHelper('Data')->jsonEncode($data['key_features']);
        $data['other_features'] = $this->getHelper('Data')->jsonEncode($data['other_features']);
        $data['attributes']     = $this->getHelper('Data')->jsonEncode(
            $this->getComparedData($data, 'attributes_name', 'attributes_value')
        );

        return $data;
    }

    protected function getComparedData($data, $keyName, $valueName)
    {
        $result = [];

        if (!isset($data[$keyName]) || !isset($data[$valueName])) {
            return $result;
        }

        $keyData = array_filter($data[$keyName]);
        $valueData = array_filter($data[$valueName]);

        if (count($keyData) !== count($valueData)) {
            return $result;
        }

        foreach ($keyData as $index => $value) {
            $result[] = ['name' => $value, 'value' => $valueData[$index]];
        }

        return $result;
    }

    public function getDefaultData()
    {
        return [
            'id'             => '',
            'title'          => '',

            'title_mode'     => Description::TITLE_MODE_PRODUCT,
            'title_template' => '',

            'brand_mode'             => Description::BRAND_MODE_CUSTOM_VALUE,
            'brand_custom_value'     => '',
            'brand_custom_attribute' => '',

            'manufacturer_mode'             => Description::MANUFACTURER_MODE_NONE,
            'manufacturer_custom_value'     => '',
            'manufacturer_custom_attribute' => '',

            'manufacturer_part_number_mode'             => Description::MANUFACTURER_PART_NUMBER_MODE_NONE,
            'manufacturer_part_number_custom_value'     => '',
            'manufacturer_part_number_custom_attribute' => '',

            // ---

            'model_number_mode'             => Description::MODEL_NUMBER_MODE_NONE,
            'model_number_custom_value'     => '',
            'model_number_custom_attribute' => '',

            'total_count_mode'             => Description::TOTAL_COUNT_MODE_NONE,
            'total_count_custom_value'     => '',
            'total_count_custom_attribute' => '',

            'count_per_pack_mode'             => Description::COUNT_PER_PACK_MODE_NONE,
            'count_per_pack_custom_value'     => '',
            'count_per_pack_custom_attribute' => '',

            'multipack_quantity_mode'             => Description::MULTIPACK_QUANTITY_MODE_NONE,
            'multipack_quantity_custom_value'     => '',
            'multipack_quantity_custom_attribute' => '',

            // ---

            'msrp_rrp_mode'             => Description::MSRP_RRP_MODE_NONE,
            'msrp_rrp_custom_attribute' => '',

            // ---

            'description_mode'     => Description::DESCRIPTION_MODE_PRODUCT,
            'description_template' => '',

            'image_main_mode'      => Description::IMAGE_MAIN_MODE_PRODUCT,
            'image_main_attribute' => '',

            'image_variation_difference_mode'      => Description::IMAGE_VARIATION_DIFFERENCE_MODE_NONE,
            'image_variation_difference_attribute' => '',

            'gallery_images_mode'      => Description::GALLERY_IMAGES_MODE_NONE,
            'gallery_images_limit'     => 1,
            'gallery_images_attribute' => '',

            'key_features_mode' => Description::KEY_FEATURES_MODE_NONE,
            'key_features'      => $this->getHelper('Data')->jsonEncode([]),

            'other_features_mode' => Description::OTHER_FEATURES_MODE_NONE,
            'other_features'      => $this->getHelper('Data')->jsonEncode([]),

            'keywords_mode'             => Description::KEYWORDS_MODE_NONE,
            'keywords_custom_value'     => '',
            'keywords_custom_attribute' => '',

            'attributes_mode' => Description::ATTRIBUTES_MODE_NONE,
            'attributes'      => $this->getHelper('Data')->jsonEncode([]),
            'attributes_name' => $this->getHelper('Data')->jsonEncode([]),
            'attributes_value' => $this->getHelper('Data')->jsonEncode([])
        ];
    }

    //########################################
}
