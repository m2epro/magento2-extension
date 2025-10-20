<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Amazon;

class PriceTracker extends \Ess\M2ePro\Model\ChangeTracker\Base\AbstractPriceTracker
{
    protected function getOnlinePriceCondition(): string
    {
        return 'IFNULL(c_lp.online_regular_price, 0)';
    }

    protected function productSubQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
    {
        $query = parent::productSubQuery();
        $query->andWhere('c_lp.is_repricing = 0');

        return $query;
    }

    protected function getMarketplaceCurrencyField(): string
    {
        return 'default_currency';
    }
}
