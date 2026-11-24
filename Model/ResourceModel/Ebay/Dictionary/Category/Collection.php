<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Dictionary\Category;

/**
 * @method \Ess\M2ePro\Model\Ebay\Dictionary\Category getFirstItem()
 * @method \Ess\M2ePro\Model\Ebay\Dictionary\Category[] getItems()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Dictionary\Category::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Dictionary\Category::class
        );
    }

    //########################################
}
