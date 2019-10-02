<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Auto\Category;

/**
 * @method \Ess\M2ePro\Model\Listing\Auto\Category\Group getParentObject()
 */
class Group extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Auto\Category\Group');
    }

    //########################################

    public function getAddingDescriptionTemplateId()
    {
        return $this->getData('adding_description_template_id');
    }

    //########################################
}
