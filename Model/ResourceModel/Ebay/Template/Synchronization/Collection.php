<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template\Synchronization;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Child\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Template\Synchronization::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Synchronization::class
        );
    }
}
