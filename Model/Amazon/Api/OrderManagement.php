<?php

namespace Ess\M2ePro\Model\Amazon\Api;

class OrderManagement implements \Ess\M2ePro\Api\Amazon\OrderManagementInterface
{
    /** @var \Ess\M2ePro\Model\Amazon\OrderFactory */
    private $amazonOrderFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Order */
    private $amazonOrderResource;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\OrderFactory $amazonOrderFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Order $amazonOrderResource
    ) {
        $this->amazonOrderFactory = $amazonOrderFactory;
        $this->amazonOrderResource = $amazonOrderResource;
    }

    /**
     * @param string $amazonOrderId
     *
     * @return \Ess\M2ePro\Model\Amazon\Order
     * @throws \Ess\M2ePro\Api\Exception\NotFoundException
     */
    private function findOrder(string $amazonOrderId): \Ess\M2ePro\Model\Amazon\Order
    {
        $order = $this->amazonOrderFactory->create();
        $this->amazonOrderResource->load($order, $amazonOrderId, 'amazon_order_id');

        if ($order->getId() === null) {
            throw new \Ess\M2ePro\Api\Exception\NotFoundException('Order not found', [
                'amazonOrderId' => $amazonOrderId,
            ]);
        }

        return $order;
    }

    public function getOrderFees(string $amazonOrderId): array
    {
        return $this->findOrder($amazonOrderId)->getFinalFees();
    }
}
