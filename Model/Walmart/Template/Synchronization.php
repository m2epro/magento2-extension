<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template;

use Ess\M2ePro\Model\Template\Synchronization as TemplateSynchronization;

/**
 * @method \Ess\M2ePro\Model\Template\Synchronization getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Synchronization getResource()
 */
class Synchronization extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
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
     * @return bool
     */
    public function isListMode()
    {
        return $this->getData('list_mode') != 0;
    }

    /**
     * @return bool
     */
    public function isListStatusEnabled()
    {
        return $this->getData('list_status_enabled') != 0;
    }

    /**
     * @return bool
     */
    public function isListIsInStock()
    {
        return $this->getData('list_is_in_stock') != 0;
    }

    /**
     * @return bool
     */
    public function isListWhenQtyCalculatedHasValue()
    {
        return $this->getData('list_qty_calculated') != TemplateSynchronization::QTY_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isListAdvancedRulesEnabled()
    {
        return $this->getData('list_advanced_rules_mode') != 0 && !empty($this->getListAdvancedRulesFilters());
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
        return $this->getReviseUpdateQtyMaxAppliedValueMode() == 1;
    }

    /**
     * @return bool
     */
    public function isReviseUpdateQtyMaxAppliedValueModeOff()
    {
        return $this->getReviseUpdateQtyMaxAppliedValueMode() == 0;
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
     * @return bool
     */
    public function isReviseUpdateQty()
    {
        return $this->getData('revise_update_qty') != 0;
    }

    /**
     * @return bool
     */
    public function isReviseUpdatePrice()
    {
        return $this->getData('revise_update_price') != 0;
    }

    /**
     * @return bool
     */
    public function isReviseUpdatePromotions()
    {
        return $this->getData('revise_update_promotions') != 0;
    }

    /**
     * @return bool
     */
    public function isReviseUpdateDetails()
    {
        return $this->getData('revise_update_details') != 0;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isRelistMode()
    {
        return $this->getData('relist_mode') != 0;
    }

    /**
     * @return bool
     */
    public function isRelistFilterUserLock()
    {
        return $this->getData('relist_filter_user_lock') != 0;
    }

    /**
     * @return bool
     */
    public function isRelistStatusEnabled()
    {
        return $this->getData('relist_status_enabled') != 0;
    }

    /**
     * @return bool
     */
    public function isRelistIsInStock()
    {
        return $this->getData('relist_is_in_stock') != 0;
    }

    /**
     * @return bool
     */
    public function isRelistWhenQtyCalculatedHasValue()
    {
        return $this->getData('relist_qty_calculated') != TemplateSynchronization::QTY_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isRelistAdvancedRulesEnabled()
    {
        return $this->getData('relist_advanced_rules_mode') != 0 && !empty($this->getRelistAdvancedRulesFilters());
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isStopMode()
    {
        return $this->getData('stop_mode') != 0;
    }

    /**
     * @return bool
     */
    public function isStopStatusDisabled()
    {
        return $this->getData('stop_status_disabled') != 0;
    }

    /**
     * @return bool
     */
    public function isStopOutOfStock()
    {
        return $this->getData('stop_out_off_stock') != 0;
    }

    /**
     * @return bool
     */
    public function isStopWhenQtyCalculatedHasValue()
    {
        return $this->getData('stop_qty_calculated') != TemplateSynchronization::QTY_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isStopAdvancedRulesEnabled()
    {
        return $this->getData('stop_advanced_rules_mode') != 0 && !empty($this->getStopAdvancedRulesFilters());
    }

    //########################################

    public function getListWhenQtyCalculatedHasValue()
    {
        return $this->getData('list_qty_calculated_value');
    }

    // ---------------------------------------

    public function getListAdvancedRulesFilters()
    {
        return $this->getData('list_advanced_rules_filters');
    }

    // ---------------------------------------

    public function getRelistWhenQtyCalculatedHasValueMin()
    {
        return $this->getData('relist_qty_calculated_value');
    }

    // ---------------------------------------

    public function getRelistAdvancedRulesFilters()
    {
        return $this->getData('relist_advanced_rules_filters');
    }

    // ---------------------------------------

    public function getStopWhenQtyCalculatedHasValueMin()
    {
        return $this->getData('stop_qty_calculated_value');
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
