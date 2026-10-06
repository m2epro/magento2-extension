<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Auto\Advanced\Filter;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Child\AbstractModel
{
    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced\Filter::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Auto\Advanced\Filter::class
        );
    }
}
