<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Bundle\Options\Mapping;

/**
 * @method \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping[] getItems()
 * @method \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Bundle\Options\Mapping::class
        );
    }
}
