<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\MSI\Magento\SourceItem;

class CollectionFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Magento $magentoHelper
    ) {
        $this->objectManager = $objectManager;
        $this->magentoHelper = $magentoHelper;
    }

    public function create(): \Magento\Inventory\Model\ResourceModel\SourceItem\Collection
    {
        if (!$this->magentoHelper->isMSISupportingVersion()) {
            throw new \LogicException('MSI not supported');
        }

        return $this->objectManager->create(
            \Magento\Inventory\Model\ResourceModel\SourceItem\Collection::class
        );
    }
}
