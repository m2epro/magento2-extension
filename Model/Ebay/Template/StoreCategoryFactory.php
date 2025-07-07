<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Template;

class StoreCategoryFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): StoreCategory
    {
        return $this->objectManager->create(StoreCategory::class);
    }
}
