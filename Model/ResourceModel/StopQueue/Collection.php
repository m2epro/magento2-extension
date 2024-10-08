<?php

namespace Ess\M2ePro\Model\ResourceModel\StopQueue;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\StopQueue::class,
            \Ess\M2ePro\Model\ResourceModel\StopQueue::class
        );
    }
}
