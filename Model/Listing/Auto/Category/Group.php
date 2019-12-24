<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Category;

/**
 * Class \Ess\M2ePro\Model\Listing\Auto\Category\Group
 */
class Group extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group');
    }

    //########################################

    /**
     * @return int
     */
    public function getListingId()
    {
        return (int)$this->getData('listing_id');
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    //########################################

    /**
     * @return int
     */
    public function getAddingMode()
    {
        return (int)$this->getData('adding_mode');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isAddingModeNone()
    {
        return $this->getAddingMode() == \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isAddingModeAdd()
    {
        return $this->getAddingMode() == \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD;
    }

    /**
     * @return bool
     */
    public function isAddingAddNotVisibleYes()
    {
        return $this->getData('adding_add_not_visible') == \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES;
    }

    //########################################

    /**
     * @return int
     */
    public function getDeletingMode()
    {
        return (int)$this->getData('deleting_mode');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isDeletingModeNone()
    {
        return $this->getDeletingMode() == \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isDeletingModeStop()
    {
        return $this->getDeletingMode() == \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP;
    }

    /**
     * @return bool
     */
    public function isDeletingModeStopRemove()
    {
        return $this->getDeletingMode() == \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE;
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\ActiveRecord\AbstractModel[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getCategories($asObjects = false, array $filters = [])
    {
        return $this->getRelatedSimpleItems('Listing_Auto_Category', 'group_id', $asObjects, $filters);
    }

    public function clearCategories()
    {
        $categories = $this->getCategories(true);
        foreach ($categories as $category) {
            $category->delete();
        }
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $items = $this->getRelatedSimpleItems('Listing_Auto_Category', 'group_id', true);
        foreach ($items as $item) {
            $item->delete();
        }

        $this->deleteChildInstance();
        return parent::delete();
    }

    //########################################
}
