<?php

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction;

class CollectionFactory
{
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function create(): Collection
    {
        return $this->objectManager->create(Collection::class);
    }
}
