<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Walmart;

class InventoryTracker extends \Ess\M2ePro\Model\ChangeTracker\Base\AbstractInventoryTracker
{
    protected function productSubQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
    {
        return parent::productSubQuery()
                     ->andWhere('c_lp.is_variation_parent = ?', 0);
    }
}
