<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder;

class QueryBuilderFactory
{
    /**
     * @return \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder
     */
    public function make(): SelectQueryBuilder
    {
        return \Magento\Framework\App\ObjectManager::getInstance()->create(SelectQueryBuilder::class);
    }
}
