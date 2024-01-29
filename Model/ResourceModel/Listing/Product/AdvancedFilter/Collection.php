<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product\AdvancedFilter;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Listing\Product\AdvancedFilter::class,
            \Ess\M2ePro\Model\ResourceModel\Listing\Product\AdvancedFilter::class
        );
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\AdvancedFilter[]
     */
    public function getAll(): array
    {
        return $this->getItems();
    }
}
