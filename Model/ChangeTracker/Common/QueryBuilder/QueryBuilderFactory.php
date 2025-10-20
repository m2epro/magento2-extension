<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder;

class QueryBuilderFactory
{
    public function createSelect(): SelectQueryBuilder
    {
        return \Magento\Framework\App\ObjectManager::getInstance()->create(SelectQueryBuilder::class);
    }
}
