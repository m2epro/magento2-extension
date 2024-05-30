<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\Discount;

/**
 * @method \Ess\M2ePro\Model\Ebay\Promotion\Discount[] getItems()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Promotion\Discount::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion\Discount::class
        );
    }
}
