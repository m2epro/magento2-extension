<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector;

class DispatcherFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): Dispatcher
    {
        return $this->objectManager->create(Dispatcher::class, $data);
    }
}
