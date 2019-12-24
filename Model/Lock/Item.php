<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Lock;

/**
 * Class \Ess\M2ePro\Model\Lock\Item
 */
class Item extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Lock\Item');
    }

    //########################################

    /**
     * This object can NOT be locked. So we are avoiding unnecessary queries to the database.
     * @return bool
     */
    public function isLocked()
    {
        return false;
    }

    public function deleteProcessingLocks($tag = false, $processingId = false)
    {
        return null;
    }

    //########################################

    public function getNick()
    {
        return $this->getData('nick');
    }

    public function getParentId()
    {
        return $this->getData('parent_id');
    }

    public function getContentData()
    {
        return $this->getData('data');
    }

    //----------------------------------------

    public function getUpdateDate()
    {
        return $this->getData('update_date');
    }

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    //########################################
}
