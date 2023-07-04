<?php

namespace Ess\M2ePro\Model\Ebay\Api;

class OrderManagement implements \Ess\M2ePro\Api\Ebay\OrderManagementInterface
{
    /** @var \Ess\M2ePro\Model\Ebay\OrderFactory */
    private $ebayOrderFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Order */
    private $ebayOrderResource;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\OrderFactory $ebayOrderFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Order $ebayOrderResource
    ) {
        $this->ebayOrderFactory = $ebayOrderFactory;
        $this->ebayOrderResource = $ebayOrderResource;
    }

    public function markAsShipment(
        int $id,
        \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TrackingDetailsInterface $trackingDetails
    ): bool {
        $order = $this->findOrder($id);

        /** @var \Ess\M2ePro\Model\Order\Item[] $orderItems */
        $orderItems = $order
            ->getParentObject()
            ->getItemsCollection()
            ->getItems();

        return $order->updateShippingStatus([
            'carrier_code' => $trackingDetails->getNumber(),
            'carrier_title' => $trackingDetails->getTitle(),
        ], $orderItems);
    }

    public function markAsPaid(int $id): bool
    {
        return $this->findOrder($id)->updatePaymentStatus();
    }

    /**
     * @param int $id
     *
     * @return \Ess\M2ePro\Model\Ebay\Order
     * @throws \Ess\M2ePro\Api\Exception\NotFoundException
     */
    private function findOrder(int $id): \Ess\M2ePro\Model\Ebay\Order
    {
        $order = $this->ebayOrderFactory->create();
        $this->ebayOrderResource->load($order, $id);

        if ($order->getId() === null) {
            throw new \Ess\M2ePro\Api\Exception\NotFoundException('Order not found', [
                'id' => $id,
            ]);
        }

        return $order;
    }
}
