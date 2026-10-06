<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Auto\Advanced;

class FilterFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Filter
    {
        return $this->objectManager->create(Filter::class);
    }
}
