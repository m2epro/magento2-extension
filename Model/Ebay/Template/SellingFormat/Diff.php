<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\SellingFormat;

class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    /**
     * @return bool
     */
    public function isDifferent(): bool
    {
        return $this->isQtyDifferent() ||
            $this->isPriceDifferent() ||
            $this->isOtherDifferent();
    }

    /**
     * @return bool
     */
    public function isQtyDifferent(): bool
    {
        $keys = [
            'qty_mode',
            'qty_custom_value',
            'qty_custom_attribute',
            'qty_percentage',
            'qty_modification_mode',
            'qty_min_posted_value',
            'qty_max_posted_value',
        ];

        return $this->isSettingsDifferent($keys);
    }

    /**
     * @return bool
     */
    public function isPriceDifferent(): bool
    {
        $keys = [
            'price_variation_mode',
            'fixed_price_mode',
            'fixed_price_modifier',
            'fixed_price_custom_attribute',
            'start_price_mode',
            'start_price_coefficient',
            'start_price_custom_attribute',
            'reserve_price_mode',
            'reserve_price_coefficient',
            'reserve_price_custom_attribute',
            'buyitnow_price_mode',
            'buyitnow_price_coefficient',
            'buyitnow_price_custom_attribute',
            'price_discount_stp_mode',
            'price_discount_stp_attribute',
            'price_discount_stp_type',
            'price_discount_map_mode',
            'price_discount_map_attribute',
            'price_discount_map_exposure_type',
            'best_offer_mode',
            'best_offer_accept_mode',
            'best_offer_accept_value',
            'best_offer_accept_attribute',
            'best_offer_reject_mode',
            'best_offer_reject_value',
            'best_offer_reject_attribute',
        ];

        return $this->isSettingsDifferent($keys);
    }

    /**
     * @return bool
     */
    public function isOtherDifferent(): bool
    {
        $keys = [
            'charity',
            'vat_mode',
            'vat_percent',
            'tax_table_mode',
            'tax_category_mode',
            'tax_category_value',
            'tax_category_attribute',
            'lot_size_mode',
            'lot_size_custom_value',
            'lot_size_attribute',
            'paypal_immediate_payment',
        ];

        return $this->isSettingsDifferent($keys);
    }
}
