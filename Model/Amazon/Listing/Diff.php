<?php

namespace Ess\M2ePro\Model\Amazon\Listing;

class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    // ----------------------------------------

    public function isDifferent()
    {
        return $this->isQtyDifferent() ||
            $this->isConditionDifferent() ||
            $this->isDetailsDifferent() ||
            $this->isSkuSettingsDifferent();
    }

    // ----------------------------------------

    public function isQtyDifferent()
    {
        $keys = [
            'handling_time_mode',
            'handling_time_value',
            'handling_time_custom_attribute',
            'restock_date_mode',
            'restock_date_value',
            'restock_date_custom_attribute',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isConditionDifferent()
    {
        $keys = [
            'condition_mode',
            'condition_value',
            'condition_custom_attribute',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isDetailsDifferent()
    {
        $keys = [
            'condition_note_mode',
            'condition_note_value',
            'gift_wrap_mode',
            'gift_wrap_attribute',
            'gift_message_mode',
            'gift_message_attribute',
        ];

        return $this->isSettingsDifferent($keys);
    }

    public function isSkuSettingsDifferent()
    {
        $keys = [
            'sku_mode',
            'sku_custom_attribute',
            'sku_modification_mode',
            'sku_modification_custom_value',
            'generate_sku_mode',
        ];

        return $this->isSettingsDifferent($keys);
    }
}
