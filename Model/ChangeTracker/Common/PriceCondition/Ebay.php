<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\PriceCondition;

use Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\ProductAttributesQueryBuilder;

class Ebay extends AbstractPriceCondition
{
    protected function loadSellingPolicyData(): array
    {
        return [];
    }
}
