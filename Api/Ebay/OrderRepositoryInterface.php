<?php

namespace Ess\M2ePro\Api\Ebay;

use Ess\M2ePro\Api\Ebay as EbayApi;

interface OrderRepositoryInterface
{
    /**
     * @param \Ess\M2ePro\Api\Ebay\OrderSearchCriteriaInterface $searchCriteria
     *
     * @return \Ess\M2ePro\Api\Ebay\OrderSearchResultInterface
     */
    public function getList(
        \Ess\M2ePro\Api\Ebay\OrderSearchCriteriaInterface $searchCriteria
    ): \Ess\M2ePro\Api\Ebay\OrderSearchResultInterface;

    /**
     * @param int $id
     *
     * @return \Ess\M2ePro\Api\Ebay\Data\OrderInterface
     */
    public function get(int $id): \Ess\M2ePro\Api\Ebay\Data\OrderInterface;
}
