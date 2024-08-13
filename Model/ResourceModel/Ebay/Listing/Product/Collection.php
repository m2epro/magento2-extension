<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product;

/**
 * @method \Ess\M2ePro\Model\Ebay\Listing\Product getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Child\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Listing\Product::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product::class
        );
    }
}
