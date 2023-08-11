<?php

namespace Ess\M2ePro\Model\Walmart\Connector;

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
