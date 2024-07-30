<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\FormDataLoader;

class FormOption
{
    private string $bundleOptionTitle;
    private ?string $mappingAttributeCode;
    private bool $usedInListing;

    public function __construct(
        string $bundleOptionTitle,
        ?string $mappingAttributeCode,
        bool $usedInListing
    ) {
        $this->bundleOptionTitle = $bundleOptionTitle;
        $this->mappingAttributeCode = $mappingAttributeCode;
        $this->usedInListing = $usedInListing;
    }

    public function getTitle(): string
    {
        return $this->bundleOptionTitle;
    }

    public function getMappingAttributeCode(): ?string
    {
        return $this->mappingAttributeCode;
    }

    public function getFieldElementId(): string
    {
        return 'mapping-' . $this->titleToId();
    }

    public function isUsedInListing(): bool
    {
        return $this->usedInListing;
    }

    public function getFieldName(): string
    {
        return sprintf('mapping[%s]', $this->getBase64EncodedTitle());
    }

    private function getBase64EncodedTitle(): string
    {
        return base64_encode($this->bundleOptionTitle);
    }

    private function titleToId(): string
    {
        return $this->getBase64EncodedTitle();
    }
}
