<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\SellingFormat;

class Builder extends \Ess\M2ePro\Model\Ebay\Template\Builder\AbstractModel
{
    //########################################

    public function build(array $data)
    {
        if (empty($data)) {
            return NULL;
        }

        $this->validate($data);

        $data = $this->prepareData($data);

        $template = $this->ebayFactory->getObject('Template\SellingFormat');

        if (isset($data['id'])) {
            $template->load($data['id']);
            $template->addData($data);
            $template->getChildObject()->addData($data);
        } else {
            $template->setData($data);
        }

        $template->save();

        return $template;
    }

    //########################################

    protected function prepareData(array &$data)
    {
        $prepared = parent::prepareData($data);

        $defaultData = $this->activeRecordFactory->getObject('Ebay\Template\SellingFormat')->getDefaultSettings();

        $data = array_merge($defaultData, $data);

        if (isset($data['listing_type'])) {
            $prepared['listing_type'] = (int)$data['listing_type'];
        }

        if (isset($data['listing_is_private'])) {
            $prepared['listing_is_private'] = (int)(bool)$data['listing_is_private'];
        }

        if (isset($data['listing_type_attribute'])) {
            $prepared['listing_type_attribute'] = $data['listing_type_attribute'];
        }

        if (isset($data['duration_mode'])) {
            $prepared['duration_mode'] = (int)$data['duration_mode'];
        }

        if (isset($data['duration_attribute'])) {
            $prepared['duration_attribute'] = $data['duration_attribute'];
        }

        if (isset($data['out_of_stock_control'])) {
            $prepared['out_of_stock_control'] = (int)$data['out_of_stock_control'];
        }

        if (isset($data['qty_mode'])) {
            $prepared['qty_mode'] = (int)$data['qty_mode'];
        }

        if (isset($data['qty_custom_value'])) {
            $prepared['qty_custom_value'] = (int)$data['qty_custom_value'];
        }

        if (isset($data['qty_custom_attribute'])) {
            $prepared['qty_custom_attribute'] = $data['qty_custom_attribute'];
        }

        if (isset($data['qty_percentage'])) {
            $prepared['qty_percentage'] = (int)$data['qty_percentage'];
        }

        if (isset($data['qty_modification_mode'])) {
            $prepared['qty_modification_mode'] = (int)$data['qty_modification_mode'];
        }

        if (isset($data['qty_min_posted_value'])) {
            $prepared['qty_min_posted_value'] = (int)$data['qty_min_posted_value'];
        }

        if (isset($data['qty_max_posted_value'])) {
            $prepared['qty_max_posted_value'] = (int)$data['qty_max_posted_value'];
        }

        if (isset($data['vat_percent'])) {
            $prepared['vat_percent'] = (float)$data['vat_percent'];
        }

        if (isset($data['tax_table_mode'])) {
            $prepared['tax_table_mode'] = (int)$data['tax_table_mode'];
        }

        if (isset($data['tax_category_mode'])) {
            $prepared['tax_category_mode'] = (int)$data['tax_category_mode'];
        }

        if (isset($data['tax_category_value'])) {
            $prepared['tax_category_value'] = $data['tax_category_value'];
        }

        if (isset($data['tax_category_attribute'])) {
            $prepared['tax_category_attribute'] = $data['tax_category_attribute'];
        }

        if (isset($data['price_increase_vat_percent'])) {
            $prepared['price_increase_vat_percent'] = (int)$data['price_increase_vat_percent'];
        }

        if (isset($data['price_variation_mode'])) {
            $prepared['price_variation_mode'] = (int)$data['price_variation_mode'];
        }

        // ---------------------------------------

        if (isset($data['fixed_price_mode'])) {
            $prepared['fixed_price_mode'] = (int)$data['fixed_price_mode'];
        }

        if (isset($data['fixed_price_coefficient'], $data['fixed_price_coefficient_mode'])) {

            $prepared['fixed_price_coefficient'] = $this->getFormattedPriceCoefficient(
                $data['fixed_price_coefficient'], $data['fixed_price_coefficient_mode']
            );
        }

        if (isset($data['fixed_price_custom_attribute'])) {
            $prepared['fixed_price_custom_attribute'] = $data['fixed_price_custom_attribute'];
        }

        // ---------------------------------------

        if (isset($data['start_price_mode'])) {
            $prepared['start_price_mode'] = (int)$data['start_price_mode'];
        }

        if (isset($data['start_price_coefficient'], $data['start_price_coefficient_mode'])) {

            $prepared['start_price_coefficient'] = $this->getFormattedPriceCoefficient(
                $data['start_price_coefficient'], $data['start_price_coefficient_mode']
            );
        }

        if (isset($data['start_price_custom_attribute'])) {
            $prepared['start_price_custom_attribute'] = $data['start_price_custom_attribute'];
        }

        // ---------------------------------------

        if (isset($data['reserve_price_mode'])) {
            $prepared['reserve_price_mode'] = (int)$data['reserve_price_mode'];
        }

        if (isset($data['reserve_price_coefficient'], $data['reserve_price_coefficient_mode'])) {

            $prepared['reserve_price_coefficient'] = $this->getFormattedPriceCoefficient(
                $data['reserve_price_coefficient'], $data['reserve_price_coefficient_mode']
            );
        }

        if (isset($data['reserve_price_custom_attribute'])) {
            $prepared['reserve_price_custom_attribute'] = $data['reserve_price_custom_attribute'];
        }

        // ---------------------------------------

        if (isset($data['buyitnow_price_mode'])) {
            $prepared['buyitnow_price_mode'] = (int)$data['buyitnow_price_mode'];
        }

        if (isset($data['buyitnow_price_coefficient'], $data['buyitnow_price_coefficient_mode'])) {

            $prepared['buyitnow_price_coefficient'] = $this->getFormattedPriceCoefficient(
                $data['buyitnow_price_coefficient'], $data['buyitnow_price_coefficient_mode']
            );
        }

        if (isset($data['buyitnow_price_custom_attribute'])) {
            $prepared['buyitnow_price_custom_attribute'] = $data['buyitnow_price_custom_attribute'];
        }

        // ---------------------------------------

        if (isset($data['price_discount_stp_mode'])) {
            $prepared['price_discount_stp_mode'] = (int)$data['price_discount_stp_mode'];
        }

        if (isset($data['price_discount_stp_attribute'])) {
            $prepared['price_discount_stp_attribute'] = $data['price_discount_stp_attribute'];
        }

        if (isset($data['price_discount_stp_type'])) {
            $prepared['price_discount_stp_type'] = (int)$data['price_discount_stp_type'];
        }

        // ---------------------------------------

        if (isset($data['price_discount_map_mode'])) {
            $prepared['price_discount_map_mode'] = (int)$data['price_discount_map_mode'];
        }

        if (isset($data['price_discount_map_attribute'])) {
            $prepared['price_discount_map_attribute'] = $data['price_discount_map_attribute'];
        }

        if (isset($data['price_discount_map_exposure_type'])) {
            $prepared['price_discount_map_exposure_type'] = (int)$data['price_discount_map_exposure_type'];
        }

        if (isset($data['restricted_to_business'])) {
            $prepared['restricted_to_business'] = (int)$data['restricted_to_business'];
        }

        // ---------------------------------------

        if (isset($data['best_offer_mode'])) {
            $prepared['best_offer_mode'] = (int)$data['best_offer_mode'];
        }

        if (isset($data['best_offer_accept_mode'])) {
            $prepared['best_offer_accept_mode'] = (int)$data['best_offer_accept_mode'];
        }

        if (isset($data['best_offer_accept_value'])) {
            $prepared['best_offer_accept_value'] = $data['best_offer_accept_value'];
        }

        if (isset($data['best_offer_accept_attribute'])) {
            $prepared['best_offer_accept_attribute'] = $data['best_offer_accept_attribute'];
        }

        if (isset($data['best_offer_reject_mode'])) {
            $prepared['best_offer_reject_mode'] = (int)$data['best_offer_reject_mode'];
        }

        if (isset($data['best_offer_reject_value'])) {
            $prepared['best_offer_reject_value'] = $data['best_offer_reject_value'];
        }

        if (isset($data['best_offer_reject_attribute'])) {
            $prepared['best_offer_reject_attribute'] = $data['best_offer_reject_attribute'];
        }

        $prepared['charity'] = NULL;

        if (!empty($data['charity']) && !empty($data['charity']['marketplace_id'])) {
            $charities = [];
            foreach ($data['charity']['marketplace_id'] as $key => $marketplaceId) {
                if (empty($data['charity']['organization_id'][$key])) {
                    continue;
                }

                $charities[$marketplaceId] = [
                    'marketplace_id' => (int)$marketplaceId,
                    'organization_id' => (int)$data['charity']['organization_id'][$key],
                    'organization_name' => $data['charity']['organization_name'][$key],
                    'organization_custom' => (int)$data['charity']['organization_custom'][$key],
                    'percentage' => (int)$data['charity']['percentage'][$key]
                ];
            }

            if (!empty($charities)) {
                $prepared['charity'] = $this->getHelper('Data')->jsonEncode($charities);
            }
        }

        if (isset($data['ignore_variations'])) {
            $prepared['ignore_variations'] = (int)$data['ignore_variations'];
        }

        return $prepared;
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
}