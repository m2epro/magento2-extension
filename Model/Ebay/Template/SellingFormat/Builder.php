<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\SellingFormat;

use Ess\M2ePro\Model\Ebay\Template\SellingFormat as SellingFormat;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\SellingFormat\Builder
 */
class Builder extends \Ess\M2ePro\Model\Ebay\Template\AbstractBuilder
{
    //########################################

    protected function prepareData()
    {
        $data = parent::prepareData();

        $data = array_merge($this->getDefaultData(), $data);

        if (isset($this->rawData['listing_type'])) {
            $data['listing_type'] = (int)$this->rawData['listing_type'];
        }

        if (isset($this->rawData['listing_is_private'])) {
            $data['listing_is_private'] = (int)(bool)$this->rawData['listing_is_private'];
        }

        if (isset($this->rawData['listing_type_attribute'])) {
            $data['listing_type_attribute'] = $this->rawData['listing_type_attribute'];
        }

        if (isset($this->rawData['duration_mode'])) {
            $data['duration_mode'] = (int)$this->rawData['duration_mode'];
        }

        if (isset($this->rawData['duration_attribute'])) {
            $data['duration_attribute'] = $this->rawData['duration_attribute'];
        }

        if (isset($this->rawData['qty_mode'])) {
            $data['qty_mode'] = (int)$this->rawData['qty_mode'];
        }

        if (isset($this->rawData['qty_custom_value'])) {
            $data['qty_custom_value'] = (int)$this->rawData['qty_custom_value'];
        }

        if (isset($this->rawData['qty_custom_attribute'])) {
            $data['qty_custom_attribute'] = $this->rawData['qty_custom_attribute'];
        }

        if (isset($this->rawData['qty_percentage'])) {
            $data['qty_percentage'] = (int)$this->rawData['qty_percentage'];
        }

        if (isset($this->rawData['qty_modification_mode'])) {
            $data['qty_modification_mode'] = (int)$this->rawData['qty_modification_mode'];
        }

        if (isset($this->rawData['qty_min_posted_value'])) {
            $data['qty_min_posted_value'] = (int)$this->rawData['qty_min_posted_value'];
        }

        if (isset($this->rawData['qty_max_posted_value'])) {
            $data['qty_max_posted_value'] = (int)$this->rawData['qty_max_posted_value'];
        }

        if (isset($this->rawData['lot_size_mode'])) {
            $data['lot_size_mode'] = (int)$this->rawData['lot_size_mode'];
        }

        if (isset($this->rawData['lot_size_custom_value'])) {
            $data['lot_size_custom_value'] = (int)$this->rawData['lot_size_custom_value'];
        }

        if (isset($this->rawData['lot_size_attribute'])) {
            $data['lot_size_attribute'] = $this->rawData['lot_size_attribute'];
        }

        if (isset($this->rawData['vat_percent'])) {
            $data['vat_percent'] = (float)$this->rawData['vat_percent'];
        }

        if (isset($this->rawData['tax_table_mode'])) {
            $data['tax_table_mode'] = (int)$this->rawData['tax_table_mode'];
        }

        if (isset($this->rawData['tax_category_mode'])) {
            $data['tax_category_mode'] = (int)$this->rawData['tax_category_mode'];
        }

        if (isset($this->rawData['tax_category_value'])) {
            $data['tax_category_value'] = $this->rawData['tax_category_value'];
        }

        if (isset($this->rawData['tax_category_attribute'])) {
            $data['tax_category_attribute'] = $this->rawData['tax_category_attribute'];
        }

        if (isset($this->rawData['price_increase_vat_percent'])) {
            $data['price_increase_vat_percent'] = (int)$this->rawData['price_increase_vat_percent'];
        }

        if (isset($this->rawData['price_variation_mode'])) {
            $data['price_variation_mode'] = (int)$this->rawData['price_variation_mode'];
        }

        // ---------------------------------------

        if (isset($this->rawData['fixed_price_mode'])) {
            $data['fixed_price_mode'] = (int)$this->rawData['fixed_price_mode'];
        }

        if (isset($this->rawData['fixed_price_coefficient'], $this->rawData['fixed_price_coefficient_mode'])) {
            $data['fixed_price_coefficient'] = $this->getFormattedPriceCoefficient(
                $this->rawData['fixed_price_coefficient'],
                $this->rawData['fixed_price_coefficient_mode']
            );
        }

        if (isset($this->rawData['fixed_price_custom_attribute'])) {
            $data['fixed_price_custom_attribute'] = $this->rawData['fixed_price_custom_attribute'];
        }

        // ---------------------------------------

        if (isset($this->rawData['start_price_mode'])) {
            $data['start_price_mode'] = (int)$this->rawData['start_price_mode'];
        }

        if (isset($this->rawData['start_price_coefficient'], $this->rawData['start_price_coefficient_mode'])) {
            $data['start_price_coefficient'] = $this->getFormattedPriceCoefficient(
                $this->rawData['start_price_coefficient'],
                $this->rawData['start_price_coefficient_mode']
            );
        }

        if (isset($this->rawData['start_price_custom_attribute'])) {
            $data['start_price_custom_attribute'] = $this->rawData['start_price_custom_attribute'];
        }

        // ---------------------------------------

        if (isset($this->rawData['reserve_price_mode'])) {
            $data['reserve_price_mode'] = (int)$this->rawData['reserve_price_mode'];
        }

        if (isset($this->rawData['reserve_price_coefficient'], $this->rawData['reserve_price_coefficient_mode'])) {
            $data['reserve_price_coefficient'] = $this->getFormattedPriceCoefficient(
                $this->rawData['reserve_price_coefficient'],
                $this->rawData['reserve_price_coefficient_mode']
            );
        }

        if (isset($this->rawData['reserve_price_custom_attribute'])) {
            $data['reserve_price_custom_attribute'] = $this->rawData['reserve_price_custom_attribute'];
        }

        // ---------------------------------------

        if (isset($this->rawData['buyitnow_price_mode'])) {
            $data['buyitnow_price_mode'] = (int)$this->rawData['buyitnow_price_mode'];
        }

        if (isset($this->rawData['buyitnow_price_coefficient'], $this->rawData['buyitnow_price_coefficient_mode'])) {
            $data['buyitnow_price_coefficient'] = $this->getFormattedPriceCoefficient(
                $this->rawData['buyitnow_price_coefficient'],
                $this->rawData['buyitnow_price_coefficient_mode']
            );
        }

        if (isset($this->rawData['buyitnow_price_custom_attribute'])) {
            $data['buyitnow_price_custom_attribute'] = $this->rawData['buyitnow_price_custom_attribute'];
        }

        // ---------------------------------------

        if (isset($this->rawData['price_discount_stp_mode'])) {
            $data['price_discount_stp_mode'] = (int)$this->rawData['price_discount_stp_mode'];
        }

        if (isset($this->rawData['price_discount_stp_attribute'])) {
            $data['price_discount_stp_attribute'] = $this->rawData['price_discount_stp_attribute'];
        }

        if (isset($this->rawData['price_discount_stp_type'])) {
            $data['price_discount_stp_type'] = (int)$this->rawData['price_discount_stp_type'];
        }

        // ---------------------------------------

        if (isset($this->rawData['price_discount_map_mode'])) {
            $data['price_discount_map_mode'] = (int)$this->rawData['price_discount_map_mode'];
        }

        if (isset($this->rawData['price_discount_map_attribute'])) {
            $data['price_discount_map_attribute'] = $this->rawData['price_discount_map_attribute'];
        }

        if (isset($this->rawData['price_discount_map_exposure_type'])) {
            $data['price_discount_map_exposure_type'] = (int)$this->rawData['price_discount_map_exposure_type'];
        }

        if (isset($this->rawData['restricted_to_business'])) {
            $data['restricted_to_business'] = (int)$this->rawData['restricted_to_business'];
        }

        // ---------------------------------------

        if (isset($this->rawData['best_offer_mode'])) {
            $data['best_offer_mode'] = (int)$this->rawData['best_offer_mode'];
        }

        if (isset($this->rawData['best_offer_accept_mode'])) {
            $data['best_offer_accept_mode'] = (int)$this->rawData['best_offer_accept_mode'];
        }

        if (isset($this->rawData['best_offer_accept_value'])) {
            $data['best_offer_accept_value'] = $this->rawData['best_offer_accept_value'];
        }

        if (isset($this->rawData['best_offer_accept_attribute'])) {
            $data['best_offer_accept_attribute'] = $this->rawData['best_offer_accept_attribute'];
        }

        if (isset($this->rawData['best_offer_reject_mode'])) {
            $data['best_offer_reject_mode'] = (int)$this->rawData['best_offer_reject_mode'];
        }

        if (isset($this->rawData['best_offer_reject_value'])) {
            $data['best_offer_reject_value'] = $this->rawData['best_offer_reject_value'];
        }

        if (isset($this->rawData['best_offer_reject_attribute'])) {
            $data['best_offer_reject_attribute'] = $this->rawData['best_offer_reject_attribute'];
        }

        $data['charity'] = null;

        if (!empty($this->rawData['charity']) && !empty($this->rawData['charity']['marketplace_id'])) {
            $charities = [];
            foreach ($this->rawData['charity']['marketplace_id'] as $key => $marketplaceId) {
                if (empty($this->rawData['charity']['organization_id'][$key])) {
                    continue;
                }

                $charities[$marketplaceId] = [
                    'marketplace_id' => (int)$marketplaceId,
                    'organization_id' => (int)$this->rawData['charity']['organization_id'][$key],
                    'organization_name' => $this->rawData['charity']['organization_name'][$key],
                    'organization_custom' => (int)$this->rawData['charity']['organization_custom'][$key],
                    'percentage' => (int)$this->rawData['charity']['percentage'][$key]
                ];
            }

            if (!empty($charities)) {
                $data['charity'] = $this->getHelper('Data')->jsonEncode($charities);
            }
        }

        if (isset($this->rawData['ignore_variations'])) {
            $data['ignore_variations'] = (int)$this->rawData['ignore_variations'];
        }

        return $data;
    }

    //########################################

    private function getFormattedPriceCoefficient($priceCoeff, $priceCoeffMode)
    {
        if ($priceCoeffMode == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_NONE) {
            return '';
        }

        $isCoefficientModeDecrease =
            $priceCoeffMode == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_ABSOLUTE_DECREASE ||
            $priceCoeffMode == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_DECREASE;

        $isCoefficientModePercentage =
            $priceCoeffMode == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_DECREASE ||
            $priceCoeffMode == \Ess\M2ePro\Model\Ebay\Template\SellingFormat::PRICE_COEFFICIENT_PERCENTAGE_INCREASE;

        $sign = $isCoefficientModeDecrease ? '-' : '+';
        $measuringSystem = $isCoefficientModePercentage ? '%' : '';

        return $sign . $priceCoeff . $measuringSystem;
    }

    //########################################

    public function getDefaultData()
    {
        return [

            'listing_type' => SellingFormat::LISTING_TYPE_FIXED,
            'listing_type_attribute' => '',

            'listing_is_private' => SellingFormat::LISTING_IS_PRIVATE_NO,

            'duration_mode' => 3,
            'duration_attribute' => '',

            'qty_mode' => \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT,
            'qty_custom_value' => 1,
            'qty_custom_attribute' => '',
            'qty_percentage' => 100,
            'qty_modification_mode' => SellingFormat::QTY_MODIFICATION_MODE_OFF,
            'qty_min_posted_value' => SellingFormat::QTY_MIN_POSTED_DEFAULT_VALUE,
            'qty_max_posted_value' => SellingFormat::QTY_MAX_POSTED_DEFAULT_VALUE,

            'vat_percent'    => 0,
            'tax_table_mode' => 0,

            'restricted_to_business' => SellingFormat::RESTRICTED_TO_BUSINESS_DISABLED,

            'tax_category_mode'      => 0,
            'tax_category_value'     => '',
            'tax_category_attribute' => '',

            'price_increase_vat_percent' => 0,
            'price_variation_mode' => SellingFormat::PRICE_VARIATION_MODE_PARENT,

            'fixed_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT,
            'fixed_price_coefficient' => '',
            'fixed_price_custom_attribute' => '',

            'start_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_PRODUCT,
            'start_price_coefficient' => '',
            'start_price_custom_attribute' => '',

            'reserve_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,
            'reserve_price_coefficient' => '',
            'reserve_price_custom_attribute' => '',

            'buyitnow_price_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,
            'buyitnow_price_coefficient' => '',
            'buyitnow_price_custom_attribute' => '',

            'price_discount_stp_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,
            'price_discount_stp_attribute' => '',
            'price_discount_stp_type' => SellingFormat::PRICE_DISCOUNT_STP_TYPE_RRP,

            'price_discount_map_mode' => \Ess\M2ePro\Model\Template\SellingFormat::PRICE_MODE_NONE,
            'price_discount_map_attribute' => '',
            'price_discount_map_exposure_type' => SellingFormat::PRICE_DISCOUNT_MAP_EXPOSURE_NONE,

            'best_offer_mode' => SellingFormat::BEST_OFFER_MODE_NO,

            'best_offer_accept_mode' => SellingFormat::BEST_OFFER_ACCEPT_MODE_NO,
            'best_offer_accept_value' => '',
            'best_offer_accept_attribute' => '',

            'best_offer_reject_mode' => SellingFormat::BEST_OFFER_REJECT_MODE_NO,
            'best_offer_reject_value' => '',
            'best_offer_reject_attribute' => '',

            'charity' => '',
            'ignore_variations' => 0,

            'lot_size_mode' => 0,
            'lot_size_custom_value' => '',
            'lot_size_attribute' => ''
        ];
    }

    //########################################
}
