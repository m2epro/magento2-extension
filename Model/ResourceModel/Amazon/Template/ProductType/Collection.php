<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType;

/**
 * @method \Ess\M2ePro\Model\Amazon\Template\ProductType[] getItems()
 * @method \Ess\M2ePro\Model\Amazon\Template\ProductType getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Amazon\Template\ProductType::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType::class
        );
    }
}
