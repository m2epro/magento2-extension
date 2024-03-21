<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Account;

class BuilderFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Builder
    {
        return $this->objectManager->create(Builder::class);
    }
}
