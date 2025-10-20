<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat;

use Ess\M2ePro\Model\Template\SellingFormat;
use Ess\M2ePro\Model\Walmart\Template\SellingFormat as WalmartSellingFormat;

class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    /** @var \Ess\M2ePro\Helper\Data */
    protected $helperData;

    /**
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->helperData = $helperData;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function prepareData(): array
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
            $data['sale_time_start_date_value'] = $this->helperData->getCurrentGmtDate(
                false,
                'Y-m-d 00:00:00'
            );
        } else {
            $data['sale_time_start_date_value'] = $this->helperData
                ->createGmtDateTime($data['sale_time_start_date_value'])
                ->format('Y-m-d 00:00:00');
        }

        if ($data['sale_time_end_date_value'] === '') {
            $data['sale_time_end_date_value'] = $this->helperData->getCurrentGmtDate(
                false,
                'Y-m-d 00:00:00'
            );
        } else {
            $data['sale_time_end_date_value'] = $this->helperData
                ->createGmtDateTime($data['sale_time_end_date_value'])
                ->format('Y-m-d 00:00:00');
        }

        $data['price_modifier'] = \Ess\M2ePro\Helper\Json::encode(
            \Ess\M2ePro\Model\Template\SellingFormat\BuilderHelper::getPriceModifierData('price', $this->rawData)
        );

        return $data;
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
            'price_modifier' => '[]',
            'price_rounding_option' => \Ess\M2ePro\Model\Listing\Product\PriceRounder::PRICE_ROUNDING_NONE,
            'price_custom_attribute' => '',

            'price_variation_mode' => WalmartSellingFormat::PRICE_VARIATION_MODE_PARENT,

            'promotions_mode' => WalmartSellingFormat::PROMOTIONS_MODE_NO,
            'promotions' => [],

            'sale_time_start_date_mode' => WalmartSellingFormat::DATE_NONE,
            'sale_time_end_date_mode' => WalmartSellingFormat::DATE_NONE,

            'sale_time_start_date_custom_attribute' => '',
            'sale_time_end_date_custom_attribute' => '',

            'sale_time_start_date_value' => $this->helperData->getCurrentGmtDate(false, 'Y-m-d'),
            'sale_time_end_date_value' => $this->helperData->getCurrentGmtDate(false, 'Y-m-d'),

            'item_weight_mode' => WalmartSellingFormat::WEIGHT_MODE_CUSTOM_ATTRIBUTE,
            'item_weight_custom_value' => '',
            'item_weight_custom_attribute' => 'weight',

            'price_vat_percent' => 0,

            'lag_time_mode' => WalmartSellingFormat::LAG_TIME_MODE_RECOMMENDED,
            'lag_time_value' => 0,
            'lag_time_custom_attribute' => '',

            'must_ship_alone_mode' => WalmartSellingFormat::MUST_SHIP_ALONE_MODE_NONE,
            'must_ship_alone_value' => '',
            'must_ship_alone_custom_attribute' => '',

            'ships_in_original_packaging_mode' => WalmartSellingFormat::SHIPS_IN_ORIGINAL_PACKAGING_MODE_NONE,
            'ships_in_original_packaging_value' => '',
            'ships_in_original_packaging_custom_attribute' => '',
        ];
    }
}
