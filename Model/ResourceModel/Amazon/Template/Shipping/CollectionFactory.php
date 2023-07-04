<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Template\Shipping;

use Ess\M2ePro\Model\ResourceModel\Amazon\Template\Shipping\Collection as AmazonTemplateShippingCollection;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): AmazonTemplateShippingCollection
    {
        return $this->objectManager->create(AmazonTemplateShippingCollection::class, $data);
    }
}
