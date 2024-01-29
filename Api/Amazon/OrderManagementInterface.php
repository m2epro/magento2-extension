<?php

namespace Ess\M2ePro\Api\Amazon;

interface OrderManagementInterface
{
    /**
     * Get Order Fees
     *
     * @param string $amazonOrderId
     *
     * @return mixed
     * @throws \Ess\M2ePro\Api\Exception\NotFoundException
     */
    public function getOrderFees(string $amazonOrderId): array;
}
