<?php

namespace Ess\M2ePro\Model\Walmart\Listing\Auto\Category;

use Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category\Group as CategoryGroupResource;

/**
 * @method \Ess\M2ePro\Model\Listing\Auto\Category\Group getParentObject()
 */
class Group extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category\Group::class);
    }

    public function isExistsProductTypeId(): bool
    {
        return $this->getDataByKey(CategoryGroupResource::COLUMN_ADDING_PRODUCT_TYPE_ID) !== null;
    }

    public function getProductTypeId(): int
    {
        return (int)$this->getDataByKey(CategoryGroupResource::COLUMN_ADDING_PRODUCT_TYPE_ID);
    }
}
