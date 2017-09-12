<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template;

/**
 * @method \Ess\M2ePro\Model\Template\Synchronization getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Synchronization getResource()
 */
class Synchronization extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    const LIST_MODE_NONE = 0;
    const LIST_MODE_YES = 1;

    const LIST_STATUS_ENABLED_NONE = 0;
    const LIST_STATUS_ENABLED_YES  = 1;

    const LIST_IS_IN_STOCK_NONE = 0;
    const LIST_IS_IN_STOCK_YES  = 1;

    const LIST_QTY_NONE    = 0;
    const LIST_QTY_LESS    = 1;
    const LIST_QTY_BETWEEN = 2;
    const LIST_QTY_MORE    = 3;

    const REVISE_UPDATE_QTY_NONE = 0;
    const REVISE_UPDATE_QTY_YES  = 1;

    const REVISE_MAX_AFFECTED_QTY_MODE_OFF = 0;
    const REVISE_MAX_AFFECTED_QTY_MODE_ON  = 1;

    const REVISE_UPDATE_QTY_MAX_APPLIED_VALUE_DEFAULT = 5;

    const REVISE_UPDATE_PRICE_NONE = 0;
    const REVISE_UPDATE_PRICE_YES  = 1;

    const REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_OFF = 0;
    const REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_ON  = 1;

    const REVISE_UPDATE_PRICE_MAX_ALLOWED_DEVIATION_DEFAULT = 3;

    const REVISE_UPDATE_DETAILS_NONE = 0;
    const REVISE_UPDATE_DETAILS_YES  = 1;

    const REVISE_UPDATE_IMAGES_NONE = 0;
    const REVISE_UPDATE_IMAGES_YES  = 1;

    const REVISE_CHANGE_DESCRIPTION_TEMPLATE_NONE = 0;
    const REVISE_CHANGE_DESCRIPTION_TEMPLATE_YES  = 1;

    const REVISE_CHANGE_SHIPPING_TEMPLATE_NONE = 0;
    const REVISE_CHANGE_SHIPPING_TEMPLATE_YES  = 1;

    const REVISE_CHANGE_PRODUCT_TAX_CODE_TEMPLATE_NONE = 0;
    const REVISE_CHANGE_PRODUCT_TAX_CODE_TEMPLATE_YES  = 1;

    const RELIST_FILTER_USER_LOCK_NONE = 0;
    const RELIST_FILTER_USER_LOCK_YES  = 1;

    const RELIST_SEND_DATA_NONE = 0;
    const RELIST_SEND_DATA_YES  = 1;

    const RELIST_MODE_NONE = 0;
    const RELIST_MODE_YES  = 1;

    const RELIST_STATUS_ENABLED_NONE = 0;
    const RELIST_STATUS_ENABLED_YES  = 1;

    const RELIST_IS_IN_STOCK_NONE = 0;
    const RELIST_IS_IN_STOCK_YES  = 1;

    const RELIST_QTY_NONE    = 0;
    const RELIST_QTY_LESS    = 1;
    const RELIST_QTY_BETWEEN = 2;
    const RELIST_QTY_MORE    = 3;

    const STOP_STATUS_DISABLED_NONE = 0;
    const STOP_STATUS_DISABLED_YES  = 1;

    const STOP_OUT_OFF_STOCK_NONE = 0;
    const STOP_OUT_OFF_STOCK_YES  = 1;

    const STOP_QTY_NONE    = 0;
    const STOP_QTY_LESS    = 1;
    const STOP_QTY_BETWEEN = 2;
    const STOP_QTY_MORE    = 3;

    const ADVANCED_RULES_MODE_NONE = 0;
    const ADVANCED_RULES_MODE_YES  = 1;

    const LIST_ADVANCED_RULES_PREFIX   = 'amazon_template_synchronization_list_advanced_rules';
    const RELIST_ADVANCED_RULES_PREFIX = 'amazon_template_synchronization_relist_advanced_rules';
    const STOP_ADVANCED_RULES_PREFIX   = 'amazon_template_synchronization_stop_advanced_rules';

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Template\Synchronization');
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

        return (bool)$this->activeRecordFactory->getObject('Amazon\Listing')
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
    public function getListings($asObjects = false, array $filters = array())
    {
        return $this->getRelatedComponentItems('Listing','template_synchronization_id',$asObjects,$filters);
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

    /**
     * @return bool
     */
    public function isReviseWhenChangeQty()
    {
        return $this->getData('revise_update_qty') != self::REVISE_UPDATE_QTY_NONE;
    }

    /**
     * @return bool
     */
    public function isReviseWhenChangePrice()
    {
        return $this->getData('revise_update_price') != self::REVISE_UPDATE_PRICE_NONE;
    }

    /**
     * @return bool
     */
    public function isReviseWhenChangeDetails()
    {
        return $this->getData('revise_update_details') != self::REVISE_UPDATE_DETAILS_NONE;
    }

    /**
     * @return bool
     */
    public function isReviseWhenChangeImages()
    {
        return $this->getData('revise_update_images') != self::REVISE_UPDATE_IMAGES_NONE;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isReviseDescriptionTemplate()
    {
        return $this->getData('revise_change_description_template') != self::REVISE_CHANGE_DESCRIPTION_TEMPLATE_NONE;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isReviseShippingTemplate()
    {
        return $this->getData('revise_change_shipping_template') !=
            self::REVISE_CHANGE_SHIPPING_TEMPLATE_NONE;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isReviseProductTaxCodeTemplate()
    {
        return $this->getData('revise_change_product_tax_code_template') !=
            self::REVISE_CHANGE_PRODUCT_TAX_CODE_TEMPLATE_NONE;
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
    public function isRelistSendData()
    {
        return $this->getData('relist_send_data') != self::RELIST_SEND_DATA_NONE;
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

    /**
     * @param bool $asArrays
     * @param string|array $columns
     * @param bool $onlyPhysicalUnits
     * @return array
     */
    public function getAffectedListingsProducts($asArrays = true, $columns = '*', $onlyPhysicalUnits = false)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection $listingCollection */
        $listingCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing'
        )->getCollection();
        $listingCollection->addFieldToFilter('template_synchronization_id', $this->getId());
        $listingCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingCollection->getSelect()->columns('id');

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing\Product'
        )->getCollection();
        $listingProductCollection->addFieldToFilter('listing_id',array('in' => $listingCollection->getSelect()));

        if ($onlyPhysicalUnits) {
            $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        }

        if (is_array($columns) && !empty($columns)) {
            $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $listingProductCollection->getSelect()->columns($columns);
        }

        return $asArrays ? (array)$listingProductCollection->getData() : (array)$listingProductCollection->getItems();
    }

    public function setSynchStatusNeed($newData, $oldData)
    {
        $listingsProducts = $this->getAffectedListingsProducts(true, array('id', 'synch_status', 'synch_reasons'));
        if (empty($listingsProducts)) {
            return;
        }

        $this->getResource()->setSynchStatusNeed($newData,$oldData,$listingsProducts);
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