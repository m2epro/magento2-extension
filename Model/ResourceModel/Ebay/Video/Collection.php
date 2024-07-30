<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Video;

/**
 * @method \Ess\M2ePro\Model\Ebay\Video[] getItems()
 * @method \Ess\M2ePro\Model\Ebay\Video getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Video::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Video::class
        );
    }
}
