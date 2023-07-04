<?php

namespace Ess\M2ePro\Model\Ebay\Api;

class OrderRepository implements \Ess\M2ePro\Api\Ebay\OrderRepositoryInterface
{
    /** @var \Ess\M2ePro\Api\Ebay\OrderSearchResultInterfaceFactory */
    private $orderSearchResultFactory;
    /** @var \Ess\M2ePro\Model\Ebay\Api\DataSources\OrdersFactory */
    private $ordersDataSourceFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Api\DataSources\OrdersFactory $ordersDataSourceFactory,
        \Ess\M2ePro\Api\Ebay\OrderSearchResultInterfaceFactory $orderSearchResult
    ) {
        $this->orderSearchResultFactory = $orderSearchResult;
        $this->ordersDataSourceFactory = $ordersDataSourceFactory;
    }

    public function getList(
        \Ess\M2ePro\Api\Ebay\OrderSearchCriteriaInterface $searchCriteria
    ): \Ess\M2ePro\Api\Ebay\OrderSearchResultInterface {
        $result = $this->orderSearchResultFactory->create();
        $this->ordersDataSourceFactory
            ->create()
            ->findByCriteria($searchCriteria, $result);

        return $result;
    }

    public function get(int $id): \Ess\M2ePro\Api\Ebay\Data\OrderInterface
    {
        return $this->ordersDataSourceFactory
            ->create()
            ->findOne($id);
    }
}
