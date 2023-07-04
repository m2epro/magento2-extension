<?php

namespace Ess\M2ePro\Model\ResourceModel\Template\Description;

use Ess\M2ePro\Model\ResourceModel\Template\Description\Collection as TemplateDescriptionCollection;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): TemplateDescriptionCollection
    {
        return $this->objectManager->create(TemplateDescriptionCollection::class, $data);
    }
    public function createWithAmazonChildMode(): TemplateDescriptionCollection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
    }

    public function createWithEbayChildMode(): TemplateDescriptionCollection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
    }

    public function createWithWalmartChildMode(): TemplateDescriptionCollection
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
    }

    private function createWithChildMode(string $componentMode): TemplateDescriptionCollection
    {
        return $this->objectManager->create(TemplateDescriptionCollection::class, [
            'childMode' => $componentMode,
        ]);
    }
}
