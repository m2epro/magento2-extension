<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Category\Specific\Validation\Result;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Category\Specific\Validation\Result::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Category\Specific\Validation\Result::class
        );
    }
}
