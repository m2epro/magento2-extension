<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Magento\Order;

class UpdaterFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Updater
    {
        return $this->objectManager->create(Updater::class);
    }
}
