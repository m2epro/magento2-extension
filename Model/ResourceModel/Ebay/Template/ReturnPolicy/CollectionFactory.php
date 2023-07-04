<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template\ReturnPolicy;

use Ess\M2ePro\Model\ResourceModel\Ebay\Template\ReturnPolicy\Collection as EbayTemplateReturnPolicyCollection;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): EbayTemplateReturnPolicyCollection
    {
        return $this->objectManager->create(Collection::class, $data);
    }
}
