<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\Promotion;

use Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\Promotion\Collection as PromotionCollection;

class CollectionFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): PromotionCollection
    {
        return $this->objectManager->create(PromotionCollection::class, $data);
    }
}
