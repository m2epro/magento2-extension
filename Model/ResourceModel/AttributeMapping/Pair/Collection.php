<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\AttributeMapping\Pair;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\AttributeMapping\Pair::class,
            \Ess\M2ePro\Model\ResourceModel\AttributeMapping\Pair::class
        );
    }
}
