<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Magento\Product\Filter;

class ExcludeSimpleProductsInVariation
{
    private \Ess\M2ePro\Model\ResourceModel\Listing $listingResource;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation $listingProductVariationResource;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\Option $listingProductVariationOptionResource;
    private \Magento\Framework\App\ResourceConnection $resource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing $listingResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation $listingProductVariationResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\Option $listingProductVariationOptionResource,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->listingResource = $listingResource;
        $this->listingProductResource = $listingProductResource;
        $this->listingProductVariationResource = $listingProductVariationResource;
        $this->listingProductVariationOptionResource = $listingProductVariationOptionResource;
        $this->resource = $resource;
    }

    public function filter(\Magento\Framework\Data\Collection $collection, int $listingId): void
    {
        $excludeSelect = $this->excludeSelect($listingId);

        $collection->getSelect()->where('e.entity_id NOT IN (?)', $excludeSelect);
    }

    private function excludeSelect(int $listingId): \Magento\Framework\DB\Select
    {
        return $this->resource
            ->getConnection()
            ->select()
            ->distinct()
            ->from(['listing' => $this->listingResource->getMainTable()], 'variation_option.product_id')
            ->joinInner(
                ['product' => $this->listingProductResource->getMainTable()],
                sprintf(
                    'product.%s = listing.%s',
                    \Ess\M2ePro\Model\ResourceModel\Listing\Product::LISTING_ID_FIELD,
                    \Ess\M2ePro\Model\ResourceModel\Listing::COLUMN_ID
                ),
                []
            )
            ->joinInner(
                ['variation' => $this->listingProductVariationResource->getMainTable()],
                sprintf(
                    'variation.listing_product_id = product.%s',
                    \Ess\M2ePro\Model\ResourceModel\Listing\Product::COLUMN_ID
                ),
                []
            )
            ->joinInner(
                ['variation_option' => $this->listingProductVariationOptionResource->getMainTable()],
                'variation_option.listing_product_variation_id = variation.id',
                []
            )
            ->where('listing.id = ?', $listingId);
    }
}
