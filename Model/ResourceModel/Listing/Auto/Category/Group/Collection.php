<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group;

use Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group as GroupResource;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Parent\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Listing\Auto\Category\Group::class,
            \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category\Group::class
        );
    }

    /**
     * @return void
     */
    public function whereAddingOrDeletingModeEnabled(): void
    {
        $addingField = GroupResource::ADDING_MODE_FIELD;
        $addingModeNone = \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE;

        $deletingField = GroupResource::DELETING_MODE_FIELD;
        $deletingModeNone = \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE;

        $this->getSelect()->where("$addingField <> $addingModeNone OR $deletingField <> $deletingModeNone");
    }
}
