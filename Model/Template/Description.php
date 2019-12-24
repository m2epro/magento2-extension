<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Template;

/**
 * Class \Ess\M2ePro\Model\Template\Description
 */
class Description extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Template\Description');
    }

    //########################################

    public function save($reloadOnCreate = false)
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_description');
        return parent::save($reloadOnCreate);
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_description');

        return parent::delete();
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    // ---------------------------------------

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    public function getUpdateDate()
    {
        return $this->getData('update_date');
    }

    //########################################

    public function getTrackingAttributes()
    {
        return $this->getChildObject()->getTrackingAttributes();
    }

    public function getUsedAttributes()
    {
        return $this->getChildObject()->getUsedAttributes();
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
