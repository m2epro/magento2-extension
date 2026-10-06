<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Order;

class AddressFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createShippingAddress(\Ess\M2ePro\Model\Order $order, array $data): ShippingAddress
    {
        return $this->objectManager->create(ShippingAddress::class, [
            'order' => $order,
            'data' => $data
        ]);
    }

    public function createBillingAddress(\Ess\M2ePro\Model\Order $order, array $data): BillingAddress
    {
        return $this->objectManager->create(BillingAddress::class, [
            'order' => $order,
            'data' => $data
        ]);
    }
}
