<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Promotion;

/**
 * @method \Ess\M2ePro\Model\Ebay\Listing\Product\Promotion[] getItems()
 * @method \Ess\M2ePro\Model\Ebay\Listing\Product\Promotion getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    protected function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Listing\Product\Promotion::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Promotion::class
        );
    }
}
