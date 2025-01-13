<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AmazonMcf\Amazon\Provider\Order;

class Repository
{
    private \Ess\M2ePro\Model\Amazon\Order\Repository $orderRepository;

    public function __construct(\Ess\M2ePro\Model\Amazon\Order\Repository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function isExistsWithMagentoOrder(int $magentoOrderId): bool
    {
        return $this->orderRepository->findByMagentoOrderId($magentoOrderId) !== null;
    }
}
