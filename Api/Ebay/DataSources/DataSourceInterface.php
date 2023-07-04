<?php

namespace Ess\M2ePro\Api\Ebay\DataSources;

interface DataSourceInterface
{
    /**
     * @param \Ess\M2ePro\Api\Ebay\OrderSearchCriteriaInterface $searchCriteria
     * @param \Ess\M2ePro\Api\Ebay\OrderSearchResultInterface $searchResult
     *
     * @return \Ess\M2ePro\Api\Ebay\OrderSearchResultInterface
     */
    public function findByCriteria(
        \Ess\M2ePro\Api\Ebay\OrderSearchCriteriaInterface $searchCriteria,
        \Ess\M2ePro\Api\Ebay\OrderSearchResultInterface $searchResult
    ): \Ess\M2ePro\Api\Ebay\OrderSearchResultInterface;

    /**
     * @param int $id
     *
     * @return \Ess\M2ePro\Api\Ebay\Data\OrderInterface
     * @throw \Ess\M2ePro\Api\Exception\NotFoundException
     */
    public function findOne(int $id): \Ess\M2ePro\Api\Ebay\Data\OrderInterface;
}
