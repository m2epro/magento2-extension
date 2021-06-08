<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat;

use Ess\M2ePro\Model\Template\SellingFormat;
use Ess\M2ePro\Model\Walmart\Template\SellingFormat as WalmartSellingFormat;

/**
 * Class Ess\M2ePro\Model\Walmart\Template\SellingFormat\Builder
 */
class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    //########################################

    protected function prepareData()
    {
        $data = [];

        $keys = array_keys($this->getDefaultData());

        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        $data['title'] = strip_tags($data['title']);

        if ($data['sale_time_start_date_value'] === '') {
            $data['sale_time_start_date_value'] = $this->getHelper('Data')->getCurrentGmtDate(
                false,
                'Y-m-d 00:00:00'
            );
        } else {
            $data['sale_time_start_date_value'] = $this->getHelper('Data')->getDate(
                $data['sale_time_start_date_value'],
                false,
                'Y-m-d 00:00:00'
            );
        }

        if ($data['sale_time_end_date_value'] === '') {
            $data['sale_time_end_date_value'] = $this->getHelper('Data')->getCurrentGmtDate(
                false,
                'Y-m-d 00:00:00'
            );
        } else {
            $data['sale_time_end_date_value'] = $this->getHelper('Data')->getDate(
                $data['sale_time_end_date_value'],
                false,
                'Y-m-d 00:00:00'
            );
        }

        $data['attributes'] = $this->getHelper('Data')->jsonEncode(
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
            'title' => '',
            'marketplace_id' => '',

            'qty_mode' => SellingFormat::QTY_MODE_PRODUCT,
            'qty_custom_value' => 1,
            'qty_custom_attribute' => '',
            'qty_percentage' => 100,
            'qty_modification_mode' => WalmartSellingFormat::QTY_MODIFICATION_MODE_OFF,
            'qty_min_posted_value' => WalmartSellingFormat::QTY_MIN_POSTED_DEFAULT_VALUE,
            'qty_max_posted_value' => WalmartSellingFormat::QTY_MAX_POSTED_DEFAULT_VALUE,

            'price_mode' => SellingFormat::PRICE_MODE_PRODUCT,
            'price_coefficient' => '',
            'price_custom_attribute' => '',

            'map_price_mode' => SellingFormat::PRICE_MODE_NONE,
            'map_price_custom_attribute' => '',

            'price_variation_mode' => WalmartSellingFormat::PRICE_VARIATION_MODE_PARENT,

            'promotions_mode' => WalmartSellingFormat::PROMOTIONS_MODE_NO,
            'promotions' => [],

            'sale_time_start_date_mode' => WalmartSellingFormat::DATE_NONE,
            'sale_time_end_date_mode' => WalmartSellingFormat::DATE_NONE,

            'sale_time_start_date_custom_attribute' => '',
            'sale_time_end_date_custom_attribute' => '',

            'sale_time_start_date_value' => $this->getHelper('Data')->getCurrentGmtDate(false, 'Y-m-d'),
            'sale_time_end_date_value' => $this->getHelper('Data')->getCurrentGmtDate(false, 'Y-m-d'),

            'item_weight_mode' => WalmartSellingFormat::WEIGHT_MODE_CUSTOM_VALUE,
            'item_weight_custom_value' => '',
            'item_weight_custom_attribute' => '',

            'price_vat_percent' => 0,

            'lag_time_mode' => WalmartSellingFormat::LAG_TIME_MODE_RECOMMENDED,
            'lag_time_value' => 0,
            'lag_time_custom_attribute' => '',

            'product_tax_code_mode' => WalmartSellingFormat::PRODUCT_TAX_CODE_MODE_VALUE,
            'product_tax_code_custom_value' => '',
            'product_tax_code_custom_attribute' => '',

            'must_ship_alone_mode' => WalmartSellingFormat::MUST_SHIP_ALONE_MODE_NONE,
            'must_ship_alone_value' => '',
            'must_ship_alone_custom_attribute' => '',

            'ships_in_original_packaging_mode' => WalmartSellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_NONE,
            'ships_in_original_packaging_value' => '',
            'ships_in_original_packaging_custom_attribute' => '',

            'attributes_mode' => WalmartSellingFormat::ATTRIBUTES_MODE_NONE,
            'attributes' => '',
            'attributes_name' => $this->getHelper('Data')->jsonEncode([]),
            'attributes_value' => $this->getHelper('Data')->jsonEncode([]),

            'shipping_override_rule_mode' => WalmartSellingFormat::SHIPPING_OVERRIDE_RULE_MODE_NO,
            'shipping_override_rule' => []
        ];
    }

    //########################################
}
