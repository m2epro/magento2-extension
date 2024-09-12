<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType;

/**
 * @method \Ess\M2ePro\Model\Amazon\Dictionary\ProductType[] getItems()
 * @method \Ess\M2ePro\Model\Amazon\Dictionary\ProductType getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Amazon\Dictionary\ProductType::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType::class
        );
    }
}
