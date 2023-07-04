<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat\BusinessDiscount;

use Ess\M2ePro\Model\ResourceModel\Amazon\Template\SellingFormat\BusinessDiscount\Collection as DiscountCollection;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): DiscountCollection
    {
        return $this->objectManager->create(DiscountCollection::class, $data);
    }
}
