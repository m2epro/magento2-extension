<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Template;

class SellingFormatFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): SellingFormat
    {
        return $this->objectManager->create(SellingFormat::class);
    }

    public function createWithAmazonChildMode(): SellingFormat
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
    }

    public function createWithEbayChildMode(): SellingFormat
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
    }

    public function createWithWalmartChildMode(): SellingFormat
    {
        return $this->createWithChildMode(\Ess\M2ePro\Helper\Component\Walmart::NICK);
    }

    private function createWithChildMode(string $childMode): SellingFormat
    {
        $instance = $this->objectManager->create(SellingFormat::class);
        $instance->setChildMode($childMode);

        return $instance;
    }
}
