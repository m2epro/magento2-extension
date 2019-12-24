<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Template;

/**
 * Class \Ess\M2ePro\Model\Template\Synchronization
 */
class Synchronization extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    const REVISE_CHANGE_LISTING_NONE = 0;
    const REVISE_CHANGE_LISTING_YES  = 1;

    const REVISE_CHANGE_SELLING_FORMAT_TEMPLATE_NONE = 0;
    const REVISE_CHANGE_SELLING_FORMAT_TEMPLATE_YES  = 1;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Template\Synchronization');
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isReviseListing()
    {
        return (int)$this->getData('revise_change_listing') != self::REVISE_CHANGE_LISTING_NONE;
    }

    /**
     * @return bool
     */
    public function isReviseSellingFormatTemplate()
    {
        return (int)$this->getData('revise_change_selling_format_template') !=
            self::REVISE_CHANGE_SELLING_FORMAT_TEMPLATE_NONE;
    }

    //########################################

    public function save($reloadOnCreate = false)
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_synchronization');
        return parent::save($reloadOnCreate);
    }

    public function delete()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_synchronization');
        return parent::delete();
    }

    //########################################

    public function getCacheGroupTags()
    {
        return array_merge(parent::getCacheGroupTags(), ['template']);
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
