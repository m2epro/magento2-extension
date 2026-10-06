<?php

namespace Ess\M2ePro\Model\ResourceModel\Order;

/**
 * @method \Ess\M2ePro\Model\Order[] getItems()
 * @method \Ess\M2ePro\Model\Order getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Parent\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Order::class,
            \Ess\M2ePro\Model\ResourceModel\Order::class
        );
    }

    public function appendFilterIds(array $ids): self
    {
        $this->addFieldToFilter('id', ['in' => $ids]);

        return $this;
    }
}
