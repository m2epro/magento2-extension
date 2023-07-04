<?php

namespace Ess\M2ePro\Api\Ebay;

interface OrderManagementInterface
{
    /**
     * Mark order as shipment
     *
     * @param int $id
     * @param \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TrackingDetailsInterface $trackingDetails
     *
     * @return bool
     * @throws \Ess\M2ePro\Api\Exception\NotFoundException
     */
    public function markAsShipment(
        int $id,
        \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TrackingDetailsInterface $trackingDetails
    ): bool;

    /**
     * Mark order as paid
     *
     * @param int $id
     *
     * @return bool
     * @throws \Ess\M2ePro\Api\Exception\NotFoundException
     */
    public function markAsPaid(int $id): bool;
}
