<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product;

use Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel;

/**
 * @method \Ess\M2ePro\Model\Ebay\Listing\Wizard\Product[] getItems()
 * @method \Ess\M2ePro\Model\Ebay\Listing\Wizard\Product getFirstItem()
 */
class Collection extends AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Listing\Wizard\Product::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product::class
        );
    }
}
