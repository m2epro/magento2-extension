<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector;

class DispatcherFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Dispatcher
    {
        return $this->objectManager->create(Dispatcher::class);
    }
}
