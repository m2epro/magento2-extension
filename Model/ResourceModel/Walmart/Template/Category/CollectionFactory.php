<?php

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): \Ess\M2ePro\Model\ResourceModel\Walmart\Template\Category\Collection
    {
        return $this->objectManager->create(Collection::class, $data);
    }
}
