<?php

namespace Ess\M2ePro\Model\ChangeTracker\Amazon;

use Ess\M2ePro\Model\ChangeTracker\Base\BasePriceTracker;
use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder;

class PriceTracker extends BasePriceTracker
{
    /**
     * @return string
     */
    protected function getOnlinePriceCondition(): string
    {
        return 'IFNULL(c_lp.online_regular_price, 0)';
    }

    /**
     * @return \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
     */
    protected function productSubQuery(): SelectQueryBuilder
    {
        $query = parent::productSubQuery();
        $query->andWhere('c_lp.is_repricing = 0');

        return $query;
    }
}
