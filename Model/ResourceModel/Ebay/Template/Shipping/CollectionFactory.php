<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping;

use Ess\M2ePro\Model\ResourceModel\Ebay\Template\Shipping\Collection as EbayTemplateShippingCollection;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): EbayTemplateShippingCollection
    {
        return $this->objectManager->create(EbayTemplateShippingCollection::class, $data);
    }
}
