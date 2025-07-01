<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ui\Grid;

use Magento\Framework\Api\SearchCriteriaInterface;

trait SearchResultTrait
{
    private \Magento\Framework\Api\Search\AggregationInterface $aggregations;

    public function setItems(?array $items = null)
    {
    }

    public function getAggregations()
    {
        return $this->aggregations;
    }

    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    public function getSearchCriteria()
    {
        return null;
    }

    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria)
    {
        return $this;
    }

    public function setTotalCount($totalCount): self
    {
        return $this;
    }
}
