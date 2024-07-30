<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Listing\Product\Variation;

class UpdaterFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createEbayUpdater(): \Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Updater
    {
        return $this->objectManager->create(\Ess\M2ePro\Model\Ebay\Listing\Product\Variation\Updater::class);
    }

    public function createAmazonUpdater(): \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Updater
    {
        return $this->objectManager->create(\Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Updater::class);
    }

    public function createWalmartUpdater(): \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Updater
    {
        return $this->objectManager->create(\Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Updater::class);
    }
}
