<?php

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\Promotion;

use Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat\Promotion\Collection as PromotionCollection;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): PromotionCollection
    {
        return $this->objectManager->create(PromotionCollection::class, $data);
    }
}
