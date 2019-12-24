<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat
 */
class SellingFormat extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    const SYNCH_REASON_QTY        = 'sellingFormatTemplateQty';
    const SYNCH_REASON_LAG_TIME   = 'sellingFormatTemplateLagTime';
    const SYNCH_REASON_PRICE      = 'sellingFormatTemplatePrice';
    const SYNCH_REASON_PROMOTIONS = 'sellingFormatTemplatePromotions';
    const SYNCH_REASON_DETAILS    = 'sellingFormatTemplateDetails';

    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_template_selling_format', 'template_selling_format_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function setSynchStatusNeed($newData, $oldData, $listingsProducts)
    {
        if (empty($listingsProducts)) {
            return;
        }

        $listingsProductsIds = [];
        foreach ($listingsProducts as $listingProduct) {
            $listingsProductsIds[] = $listingProduct['id'];
        }

        $synchReasons = [];

        if ($this->isDifferentQty($newData, $oldData)) {
            $synchReasons[] = self::SYNCH_REASON_QTY;
        }

        if ($this->isDifferentLagTime($newData, $oldData)) {
            $synchReasons[] = self::SYNCH_REASON_LAG_TIME;
        }

        if ($this->isDifferentPrice($newData, $oldData)) {
            $synchReasons[] = self::SYNCH_REASON_PRICE;
        }

        if ($this->isDifferentPromotions($newData, $oldData)) {
            $synchReasons[] = self::SYNCH_REASON_PROMOTIONS;
        }

        if ($this->isDifferentDetails($newData, $oldData)) {
            $synchReasons[] = self::SYNCH_REASON_DETAILS;
        }

        if (empty($synchReasons)) {
            return;
        }

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();

        $this->getConnection()->update(
            $lpTable,
            [
                'synch_status' => \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_NEED,
                'synch_reasons' => new \Zend_Db_Expr(
                    "IF(synch_reasons IS NULL,
                        '".implode(',', $synchReasons)."',
                        CONCAT(synch_reasons,'".','.implode(',', $synchReasons)."')
                    )"
                )
            ],
            ['id IN ('.implode(',', $listingsProductsIds).')']
        );
    }

    // ---------------------------------------

    public function isDifferentQty($newData, $oldData)
    {
        list($newData, $oldData) = $this->removeIgnoredFields($newData, $oldData);

        $keys = [
            'qty_mode',
            'qty_custom_value',
            'qty_custom_attribute',
            'qty_percentage',
            'qty_modification_mode',
            'qty_min_posted_value',
            'qty_max_posted_value',
        ];

        return $this->isSettingsDifferent($keys, $newData, $oldData);
    }

    public function isDifferentLagTime($newData, $oldData)
    {
        list($newData, $oldData) = $this->removeIgnoredFields($newData, $oldData);

        $keys = [
            'lag_time_mode',
            'lag_time_value',
            'lag_time_custom_attribute',
        ];

        return $this->isSettingsDifferent($keys, $newData, $oldData);
    }

    public function isDifferentPrice($newData, $oldData)
    {
        list($newData, $oldData) = $this->removeIgnoredFields($newData, $oldData);

        $keys = [
            'price_mode',
            'price_coefficient',
            'price_custom_attribute',
            'price_variation_mode',
            'price_vat_percent',
        ];

        return $this->isSettingsDifferent($keys, $newData, $oldData);
    }

    public function isDifferentPromotions($newData, $oldData)
    {
        list($newData, $oldData) = $this->removeIgnoredFields($newData, $oldData);

        $keys = [
            'promotions'
        ];

        return $this->isSettingsDifferent($keys, $newData, $oldData);
    }

    public function isDifferentDetails($newData, $oldData)
    {
        list($newData, $oldData) = $this->removeIgnoredFields($newData, $oldData);

        $keys = [
            'map_price_mode',
            'map_price_custom_attribute',
            'sale_time_start_date_mode',
            'sale_time_start_date_value',
            'sale_time_start_date_custom_attribute',
            'sale_time_end_date_mode',
            'sale_time_end_date_value',
            'sale_time_end_date_custom_attribute',
            'product_tax_code_mode',
            'product_tax_code_custom_value',
            'product_tax_code_custom_attribute',
            'item_weight_mode',
            'item_weight_custom_value',
            'item_weight_custom_attribute',
            'must_ship_alone_mode',
            'must_ship_alone_value',
            'must_ship_alone_custom_attribute',
            'ships_in_original_packaging_mode',
            'ships_in_original_packaging_value',
            'ships_in_original_packaging_custom_attribute',
            'shipping_override_rule_mode',
            'shipping_overrides',
            'attributes_mode',
            'attributes',
        ];

        return $this->isSettingsDifferent($keys, $newData, $oldData);
    }

    // ---------------------------------------

    private function removeIgnoredFields($newData, $oldData)
    {
        $ignoreFields = [
            $this->getIdFieldName(),
            'id', 'title', 'component_mode',
            'create_date', 'update_date',
        ];

        foreach ($ignoreFields as $ignoreField) {
            unset($newData[$ignoreField], $oldData[$ignoreField]);
        }

        return [$newData, $oldData];
    }

    protected function isSettingsDifferent($keys, $newSnapshotData, $oldSnapshotData)
    {
        foreach ($keys as $key) {
            if (empty($newSnapshotData[$key]) && empty($oldSnapshotData[$key])) {
                continue;
            }

            if (empty($newSnapshotData[$key]) || empty($oldSnapshotData[$key])) {
                return true;
            }

            if ($newSnapshotData[$key] != $oldSnapshotData[$key]) {
                return true;
            }
        }

        return false;
    }

    //########################################
}
