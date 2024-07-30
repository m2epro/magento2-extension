<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\FormDataLoader;

class FormOptionFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(
        string $bundleOptionTitle,
        ?string $mappingAttributeCode,
        bool $usedInListing
    ) {
        return $this->objectManager->create(FormOption::class, [
            'bundleOptionTitle' => $bundleOptionTitle,
            'mappingAttributeCode' => $mappingAttributeCode,
            'usedInListing' => $usedInListing,
        ]);
    }
}
