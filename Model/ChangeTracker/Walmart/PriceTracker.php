<?php

namespace Ess\M2ePro\Model\ChangeTracker\Walmart;

use Ess\M2ePro\Model\ChangeTracker\Base\BasePriceTracker;

class PriceTracker extends BasePriceTracker
{
    /**
     * @return string
     */
    protected function getOnlinePriceCondition(): string
    {
        return 'IFNULL(c_lp.online_price, 0)';
    }
}
