<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Child\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Amazon\Listing::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing::class
        );
    }
}
