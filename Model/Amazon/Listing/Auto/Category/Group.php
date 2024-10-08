<?php

namespace Ess\M2ePro\Model\Amazon\Listing\Auto\Category;

use Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Auto\Category\Group as CategoryGroupResource;

class Group extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Auto\Category\Group::class);
    }

    public function getAddingProductTypeTemplateId()
    {
        return $this->getData(CategoryGroupResource::COLUMN_ADDING_PRODUCT_TYPE_TEMPLATE_ID);
    }
}
