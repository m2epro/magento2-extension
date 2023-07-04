<?php

namespace Ess\M2ePro\Model\Ebay\Api\DataSources;

class OrdersFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * @return \Ess\M2ePro\Api\Ebay\DataSources\DataSourceInterface
     */
    public function create(): \Ess\M2ePro\Api\Ebay\DataSources\DataSourceInterface
    {
        return $this->objectManager->create(\Ess\M2ePro\Model\Ebay\Api\DataSources\Orders::class);
    }
}
