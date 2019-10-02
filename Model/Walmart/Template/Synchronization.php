<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template;

/**
 * @method \Ess\M2ePro\Model\Template\Synchronization getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Synchronization getResource()
 */
class Synchronization extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    const LIST_MODE_NONE = 0;
    const LIST_MODE_YES = 1;

    const LIST_STATUS_ENABLED_NONE = 0;
    const LIST_STATUS_ENABLED_YES = 1;

    const LIST_IS_IN_STOCK_NONE = 0;
    const LIST_IS_IN_STOCK_YES = 1;

    const LIST_QTY_NONE = 0;
    const LIST_QTY_LESS = 1;
    const LIST_QTY_BETWEEN = 2;
    const LIST_QTY_MORE = 3;

    const REVISE_UPDATE_QTY_NONE = 0;
    const REVISE_UPDATE_QTY_YES = 1;

    const REVISE_MAX_AFFECTED_QTY_MODE_OFF = 0;
    const REVISE_MAX_AFFECTED_QTY_MODE_ON = 1;

    const REVISE_UPDATE_QTY_MAX_APPLIED_VALUE_DEFAULT = 5;

    const REVISE_UPDATE_PRICE_NONE = 0;
    const REVISE_UPDATE_PRICE_YES = 1;

    const REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_OFF = 0;
    const REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_ON = 1;

    const REVISE_UPDATE_PRICE_MAX_ALLOWED_DEVIATION_DEFAULT = 3;

    const REVISE_UPDATE_PROMOTIONS_NONE = 0;
    const REVISE_UPDATE_PROMOTIONS_YES = 1;

    const RELIST_FILTER_USER_LOCK_NONE = 0;
    const RELIST_FILTER_USER_LOCK_YES = 1;

    const RELIST_MODE_NONE = 0;
    const RELIST_MODE_YES = 1;

    const RELIST_STATUS_ENABLED_NONE = 0;
    const RELIST_STATUS_ENABLED_YES = 1;

    const RELIST_IS_IN_STOCK_NONE = 0;
    const RELIST_IS_IN_STOCK_YES = 1;

    const RELIST_QTY_NONE = 0;
    const RELIST_QTY_LESS = 1;
    const RELIST_QTY_BETWEEN = 2;
    const RELIST_QTY_MORE = 3;

    const STOP_MODE_NONE = 0;
    const STOP_MODE_YES = 1;

    const STOP_STATUS_DISABLED_NONE = 0;
    const STOP_STATUS_DISABLED_YES = 1;

    const STOP_OUT_OFF_STOCK_NONE = 0;
    const STOP_OUT_OFF_STOCK_YES = 1;

    const STOP_QTY_NONE = 0;
    const STOP_QTY_LESS = 1;
    const STOP_QTY_BETWEEN = 2;
    const STOP_QTY_MORE = 3;

    const ADVANCED_RULES_MODE_NONE = 0;
    const ADVANCED_RULES_MODE_YES  = 1;

    const LIST_ADVANCED_RULES_PREFIX   = 'walmart_template_synchronization_list_advanced_rules';
    const RELIST_ADVANCED_RULES_PREFIX = 'walmart_template_synchronization_relist_advanced_rules';
    const STOP_ADVANCED_RULES_PREFIX   = 'walmart_template_synchronization_stop_advanced_rules';

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Template\Synchronization');
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        return (bool)$this->activeRecordFactory->getObject('Walmart\Listing')
            ->getCollection()
            ->addFieldToFilter('template_synchronization_id', $this->getId())
            ->getSize();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getListings($asObjects = false, array $filters = [])
    {
        return $this->getRelatedComponentItems('Listing', 'template_synchronization_id', $asObjects, $filters);
    }

    //########################################

    /**
     * @return bool
     */
    public function isListMode()
    {
        return $this->getData('list_mode') != self::LIST_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isListStatusEnabled()
    {
        return $this->getData('list_status_enabled') != self::LIST_STATUS_ENABLED_NONE;
    }

    /**
     * @return bool
     */
    public function isListIsInStock()
    {
        return $this->getData('list_is_in_stock') != self::LIST_IS_IN_STOCK_NONE;
    }

    /**
     * @return bool
     */
    public function isListWhenQtyMagentoHasValue()
    {
        return $this->getData('list_qty_magento') != self::LIST_QTY_NONE;
    }

    /**
     * @return bool
     */
    public function isListWhenQtyCalculatedHasValue()
    {
        return $this->getData('list_qty_calculated') != self::LIST_QTY_NONE;
    }

    /**
     * @return bool
     */
    public function isListAdvancedRulesEnabled()
    {
        return $this->getData('list_advanced_rules_mode') != self::ADVANCED_RULES_MODE_NONE &&
            !empty($this->getListAdvancedRulesFilters());
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getReviseUpdateQtyMaxAppliedValueMode()
    {
        return (int)$this->getData('revise_update_qty_max_applied_value_mode');
    }

    /**
     * @return bool
     */
    public function isReviseUpdateQtyMaxAppliedValueModeOn()
    {
        return $this->getReviseUpdateQtyMaxAppliedValueMode() == self::REVISE_MAX_AFFECTED_QTY_MODE_ON;
    }

    /**
     * @return bool
     */
    public function isReviseUpdateQtyMaxAppliedValueModeOff()
    {
        return $this->getReviseUpdateQtyMaxAppliedValueMode() == self::REVISE_MAX_AFFECTED_QTY_MODE_OFF;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getReviseUpdateQtyMaxAppliedValue()
    {
        return (int)$this->getData('revise_update_qty_max_applied_value');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getReviseUpdatePriceMaxAllowedDeviationMode()
    {
        return (int)$this->getData('revise_update_price_max_allowed_deviation_mode');
    }

    /**
     * @return bool
     */
    public function isReviseUpdatePriceMaxAllowedDeviationModeOn()
    {
        return $this->getReviseUpdatePriceMaxAllowedDeviationMode() == self::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_ON;
    }

    /**
     * @return bool
     */
    public function isReviseUpdatePriceMaxAllowedDeviationModeOff()
    {
        return $this->getReviseUpdatePriceMaxAllowedDeviationMode()
            == self::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_OFF;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getReviseUpdatePriceMaxAllowedDeviation()
    {
        return (int)$this->getData('revise_update_price_max_allowed_deviation');
    }

    // ---------------------------------------

    public function isPriceChangedOverAllowedDeviation($onlinePrice, $currentPrice)
    {
        if ((float)$onlinePrice == (float)$currentPrice) {
            return false;
        }

        if ((float)$onlinePrice <= 0) {
            return true;
        }

        if ($this->isReviseUpdatePriceMaxAllowedDeviationModeOff()) {
            return true;
        }

        $deviation = round(abs($onlinePrice - $currentPrice) / $onlinePrice * 100, 2);

        return $deviation > $this->getReviseUpdatePriceMaxAllowedDeviation();
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isReviseUpdateQty()
    {
        return $this->getData('revise_update_qty') != self::REVISE_UPDATE_QTY_NONE;
    }

    /**
     * @return bool
     */
    public function isReviseUpdatePrice()
    {
        return $this->getData('revise_update_price') != self::REVISE_UPDATE_PRICE_NONE;
    }

    /**
     * @return bool
     */
    public function isReviseUpdatePromotions()
    {
        return $this->getData('revise_update_promotions') != self::REVISE_UPDATE_PROMOTIONS_NONE;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isRelistMode()
    {
        return $this->getData('relist_mode') != self::RELIST_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isRelistFilterUserLock()
    {
        return $this->getData('relist_filter_user_lock') != self::RELIST_FILTER_USER_LOCK_NONE;
    }

    /**
     * @return bool
     */
    public function isRelistStatusEnabled()
    {
        return $this->getData('relist_status_enabled') != self::RELIST_STATUS_ENABLED_NONE;
    }

    /**
     * @return bool
     */
    public function isRelistIsInStock()
    {
        return $this->getData('relist_is_in_stock') != self::RELIST_IS_IN_STOCK_NONE;
    }

    /**
     * @return bool
     */
    public function isRelistWhenQtyMagentoHasValue()
    {
        return $this->getData('relist_qty_magento') != self::RELIST_QTY_NONE;
    }

    /**
     * @return bool
     */
    public function isRelistWhenQtyCalculatedHasValue()
    {
        return $this->getData('relist_qty_calculated') != self::RELIST_QTY_NONE;
    }

    /**
     * @return bool
     */
    public function isRelistAdvancedRulesEnabled()
    {
        return $this->getData('relist_advanced_rules_mode') != self::ADVANCED_RULES_MODE_NONE &&
            !empty($this->getRelistAdvancedRulesFilters());
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isStopMode()
    {
        return $this->getData('stop_mode') != self::STOP_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isStopStatusDisabled()
    {
        return $this->getData('stop_status_disabled') != self::STOP_STATUS_DISABLED_NONE;
    }

    /**
     * @return bool
     */
    public function isStopOutOfStock()
    {
        return $this->getData('stop_out_off_stock') != self::STOP_OUT_OFF_STOCK_NONE;
    }

    /**
     * @return bool
     */
    public function isStopWhenQtyMagentoHasValue()
    {
        return $this->getData('stop_qty_magento') != self::STOP_QTY_NONE;
    }

    /**
     * @return bool
     */
    public function isStopWhenQtyCalculatedHasValue()
    {
        return $this->getData('stop_qty_calculated') != self::STOP_QTY_NONE;
    }

    /**
     * @return bool
     */
    public function isStopAdvancedRulesEnabled()
    {
        return $this->getData('stop_advanced_rules_mode') != self::ADVANCED_RULES_MODE_NONE &&
            !empty($this->getStopAdvancedRulesFilters());
    }

    //########################################

    public function getListWhenQtyMagentoHasValueType()
    {
        return $this->getData('list_qty_magento');
    }

    public function getListWhenQtyMagentoHasValueMin()
    {
        return $this->getData('list_qty_magento_value');
    }

    public function getListWhenQtyMagentoHasValueMax()
    {
        return $this->getData('list_qty_magento_value_max');
    }

    // ---------------------------------------

    public function getListWhenQtyCalculatedHasValueType()
    {
        return $this->getData('list_qty_calculated');
    }

    public function getListWhenQtyCalculatedHasValueMin()
    {
        return $this->getData('list_qty_calculated_value');
    }

    public function getListWhenQtyCalculatedHasValueMax()
    {
        return $this->getData('list_qty_calculated_value_max');
    }

    // ---------------------------------------

    public function getListAdvancedRulesFilters()
    {
        return $this->getData('list_advanced_rules_filters');
    }

    // ---------------------------------------

    public function getRelistWhenQtyMagentoHasValueType()
    {
        return $this->getData('relist_qty_magento');
    }

    public function getRelistWhenQtyMagentoHasValueMin()
    {
        return $this->getData('relist_qty_magento_value');
    }

    public function getRelistWhenQtyMagentoHasValueMax()
    {
        return $this->getData('relist_qty_magento_value_max');
    }

    // ---------------------------------------

    public function getRelistWhenQtyCalculatedHasValueType()
    {
        return $this->getData('relist_qty_calculated');
    }

    public function getRelistWhenQtyCalculatedHasValueMin()
    {
        return $this->getData('relist_qty_calculated_value');
    }

    public function getRelistWhenQtyCalculatedHasValueMax()
    {
        return $this->getData('relist_qty_calculated_value_max');
    }

    // ---------------------------------------

    public function getRelistAdvancedRulesFilters()
    {
        return $this->getData('relist_advanced_rules_filters');
    }

    // ---------------------------------------

    public function getStopWhenQtyMagentoHasValueType()
    {
        return $this->getData('stop_qty_magento');
    }

    public function getStopWhenQtyMagentoHasValueMin()
    {
        return $this->getData('stop_qty_magento_value');
    }

    public function getStopWhenQtyMagentoHasValueMax()
    {
        return $this->getData('stop_qty_magento_value_max');
    }

    // ---------------------------------------

    public function getStopWhenQtyCalculatedHasValueType()
    {
        return $this->getData('stop_qty_calculated');
    }

    public function getStopWhenQtyCalculatedHasValueMin()
    {
        return $this->getData('stop_qty_calculated_value');
    }

    public function getStopWhenQtyCalculatedHasValueMax()
    {
        return $this->getData('stop_qty_calculated_value_max');
    }

    // ---------------------------------------

    public function getStopAdvancedRulesFilters()
    {
        return $this->getData('stop_advanced_rules_filters');
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    public function getCacheGroupTags()
    {
        return array_merge(parent::getCacheGroupTags(), ['template']);
    }

    //########################################
}
