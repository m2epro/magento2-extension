<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\Promotion;

/**
 * @method \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion[] getItems()
 * @method \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Promotion::class,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\Promotion::class
        );
    }
}
