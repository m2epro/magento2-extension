<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Template;

class RepricerFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Repricer
    {
        return $this->objectManager->create(Repricer::class);
    }
}
