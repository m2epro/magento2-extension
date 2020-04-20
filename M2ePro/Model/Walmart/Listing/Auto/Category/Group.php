<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing\Auto\Category\Group getParentObject()
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Auto\Category;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Auto\Category\Group
 */
class Group extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category\Group');
    }

    //########################################

    public function getAddingCategoryTemplateId()
    {
        return $this->getData('adding_category_template_id');
    }

    //########################################
}
