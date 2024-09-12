<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace;

/**
 * @method \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace[] getItems()
 * @method \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace::class
        );
    }
}
