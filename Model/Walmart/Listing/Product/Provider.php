<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing\Product;

class Provider
{
    private \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct;

    public function __construct(\Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct)
    {
        $this->walmartListingProduct = $walmartListingProduct;
    }

    public function retrieveCondition(): ?Provider\Condition
    {
        $walmartListing = $this->walmartListingProduct->getWalmartListing();
        if ($walmartListing->isConditionModeNone()) {
            return null;
        }

        if ($walmartListing->isConditionModeRecommended()) {
            return Provider\Condition::createWithValue(
                $walmartListing->getConditionRecommendedValue()
            );
        }

        $attributeCode = $walmartListing->getConditionCustomAttribute();
        $attribute = $this->walmartListingProduct->getMagentoProduct()
                                                 ->getAttributeValue($attributeCode);

        return !empty($attribute)
            ? Provider\Condition::createWithValue($attribute)
            : Provider\Condition::createWithoutMagentoAttribute();
    }
}
