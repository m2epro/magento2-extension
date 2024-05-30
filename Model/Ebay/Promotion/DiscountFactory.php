<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class DiscountFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Discount
    {
        return $this->objectManager->create(Discount::class);
    }
}
