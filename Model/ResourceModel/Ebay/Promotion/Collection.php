<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Promotion;

/**
 * @method \Ess\M2ePro\Model\Ebay\Promotion[] getItems()
 * @method \Ess\M2ePro\Model\Ebay\Promotion getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Promotion::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Promotion::class
        );
    }
}
