<?php

namespace Ess\M2ePro\Model\ResourceModel\Template\SellingFormat;

use Ess\M2ePro\Model\ResourceModel\Template\SellingFormat\Collection as TemplateSellingFormatCollection;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): TemplateSellingFormatCollection
    {
        return $this->objectManager->create(TemplateSellingFormatCollection::class, $data);
    }
    public function createWithAmazonChildMode(): TemplateSellingFormatCollection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
    }

    public function createWithEbayChildMode(): TemplateSellingFormatCollection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
    }

    public function createWithWalmartChildMode(): TemplateSellingFormatCollection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
    }

    private function createWithChildMode(string $componentMode): TemplateSellingFormatCollection
    {
        return $this->objectManager->create(TemplateSellingFormatCollection::class, [
            'childMode' => $componentMode,
        ]);
    }
}
