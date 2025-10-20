<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Walmart;

class PriceTracker extends \Ess\M2ePro\Model\ChangeTracker\Base\AbstractPriceTracker
{
    protected function getOnlinePriceCondition(): string
    {
        return 'IFNULL(c_lp.online_price, 0)';
    }

    protected function getMarketplaceCurrencyField(): string
    {
        return 'default_currency';
    }
}
