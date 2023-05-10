<?php

namespace Ess\M2ePro\Model\Walmart\Dashboard\Products;

class Calculator implements \Ess\M2ePro\Model\Dashboard\Products\CalculatorInterface
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection\Factory $listingProductCollectionFactory */
    private $listingProductCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection\Factory $listingProductCollectionFactory
    ) {
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
    }

    public function getCountOfActiveProducts(): int
    {
        return $this->getCollectionSize(['eq' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED]);
    }

    public function getCountOfInactiveProducts(): int
    {
        return $this->getCollectionSize(['neq' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED]);
    }

    private function getCollectionSize(array $statusCondition): int
    {
        $collection = $this->listingProductCollectionFactory->create();
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing\Product::COMPONENT_MODE_FIELD,
            \Ess\M2ePro\Helper\Component\Walmart::NICK
        );
        $collection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing\Product::STATUS_FIELD,
            $statusCondition
        );

        return $collection->getSize();
    }
}
