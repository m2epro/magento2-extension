<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order;

class CreatorFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Creator
    {
        return $this->objectManager->create(Creator::class, ['objectManager' => $this->objectManager]);
    }
}
