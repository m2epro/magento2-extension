<?php

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category\Group;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Child\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Walmart\Listing\Auto\Category\Group::class,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category\Group::class
        );
    }
}
