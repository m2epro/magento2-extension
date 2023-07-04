<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductTaxCode;

use Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductTaxCode\Collection as ProductTaxCodeCollection;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): ProductTaxCodeCollection
    {
        return $this->objectManager->create(ProductTaxCodeCollection::class, $data);
    }
}
