<?php

namespace Ess\M2ePro\Model\Ebay;

class OrderFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Order
     */
    public function create(): \Ess\M2ePro\Model\Ebay\Order
    {
        return $this->objectManager->create(Order::class);
    }
}
