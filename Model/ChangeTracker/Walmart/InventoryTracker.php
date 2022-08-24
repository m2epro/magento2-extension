<?php

namespace Ess\M2ePro\Model\ChangeTracker\Walmart;

use Ess\M2ePro\Model\ChangeTracker\Base\BaseInventoryTracker;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder;

class InventoryTracker extends BaseInventoryTracker
{
    /**
     * @return \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
     */
    protected function productSubQuery(): SelectQueryBuilder
    {
        return parent::productSubQuery()
            ->andWhere('c_lp.is_variation_parent = ?', 0)
        ;
    }
}
