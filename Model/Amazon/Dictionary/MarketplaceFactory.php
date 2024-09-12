<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Dictionary;

class MarketplaceFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(
        \Ess\M2ePro\Model\Marketplace $marketplace,
        array $productTypes
    ): Marketplace {
        /** @var Marketplace $model */
        $model = $this->objectManager->create(Marketplace::class);
        $model->create($marketplace, $productTypes);

        return $model;
    }
}
