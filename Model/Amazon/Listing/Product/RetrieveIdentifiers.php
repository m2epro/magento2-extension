<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

class RetrieveIdentifiers
{
    private \Ess\M2ePro\Model\Amazon\Listing\ProductIdentifiersConfig $config;

    public function __construct(\Ess\M2ePro\Model\Amazon\Listing\ProductIdentifiersConfig $config)
    {
        $this->config = $config;
    }

    public function process(
        \Ess\M2ePro\Model\Amazon\Listing $amazonListing,
        \Ess\M2ePro\Model\Magento\Product $magentoProduct
    ): ?RetrieveIdentifiers\Identifiers {
        $identifiers = new RetrieveIdentifiers\Identifiers();
        if ($generalId = $this->findGeneralId($amazonListing, $magentoProduct)) {
            $identifiers->setGeneralId($generalId);
        }

        if ($worldWideId = $this->findWorldWideId($amazonListing, $magentoProduct)) {
            $identifiers->setWorldwideId($worldWideId);
        }

        return $identifiers;
    }

    private function findGeneralId(
        \Ess\M2ePro\Model\Amazon\Listing $amazonListing,
        \Ess\M2ePro\Model\Magento\Product $magentoProduct
    ): ?RetrieveIdentifiers\GeneralIdentifier {
        $attribute = $this->config->findGeneralIdAttribute($amazonListing);
        if ($attribute === null) {
            return null;
        }

        $attributeValue = $this->getAttributeValue($attribute, $magentoProduct);
        if ($attributeValue === null) {
            return null;
        }

        return new RetrieveIdentifiers\GeneralIdentifier($attributeValue);
    }

    private function findWorldWideId(
        \Ess\M2ePro\Model\Amazon\Listing $amazonListing,
        \Ess\M2ePro\Model\Magento\Product $magentoProduct
    ): ?RetrieveIdentifiers\WorldwideIdentifier {
        $attribute = $this->config->findWorldwideIdAttribute($amazonListing);
        if ($attribute === null) {
            return null;
        }

        $attributeValue = $this->getAttributeValue($attribute, $magentoProduct);
        if ($attributeValue === null) {
            return null;
        }

        return new RetrieveIdentifiers\WorldwideIdentifier($attributeValue);
    }

    private function getAttributeValue(
        string $attributeCode,
        \Ess\M2ePro\Model\Magento\Product $magentoProduct
    ): ?string {
        $value = $magentoProduct->getAttributeValue($attributeCode);
        $value = trim(str_replace('-', '', $value));

        return $value === '' ? null : $value;
    }
}
