<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay;

class ComplianceDocumentsFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): ComplianceDocuments
    {
        return $this->objectManager->create(ComplianceDocuments::class);
    }
}
