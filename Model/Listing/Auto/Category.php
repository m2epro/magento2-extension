<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto;

class Category extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Auto\Category\Group $group */
    private $group = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category');
    }

    //########################################

    /**
     * @return int
     */
    public function getGroupId()
    {
        return (int)$this->getData('group_id');
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return (int)$this->getData('category_id');
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing\Auto\Category\Group
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getGroup()
    {
        if ($this->getGroupId() <= 0) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Group ID was not set.');
        }

        if (!is_null($this->group)) {
            return $this->group;
        }

        return $this->group = $this->activeRecordFactory->getObjectLoaded(
            'Listing\Auto\Category\Group', $this->getGroupId()
        );
    }

    //########################################
}