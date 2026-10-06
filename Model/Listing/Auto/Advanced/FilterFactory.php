<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Listing\Auto\Advanced;

class FilterFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createWithComponentModeEbay(): Filter
    {
        return $this->objectManager
            ->create(Filter::class)
            ->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
    }

    public function createWithComponentModeAmazon(): Filter
    {
        return $this->objectManager
            ->create(Filter::class)
            ->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
    }
}
