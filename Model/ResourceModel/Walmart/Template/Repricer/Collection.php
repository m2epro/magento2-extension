<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer;

/**
 * @method \Ess\M2ePro\Model\Walmart\Template\Repricer getFirstItem()
 * @method \Ess\M2ePro\Model\Walmart\Template\Repricer[] getItems()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Walmart\Template\Repricer::class,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Repricer::class
        );
    }
}
