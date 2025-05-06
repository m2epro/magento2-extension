<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing\Product;

class Repository
{
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product $walmartProductResource;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $productCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product $walmartProductResource,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $productCollectionFactory
    ) {
        $this->walmartProductResource = $walmartProductResource;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function findFirstByIds(array $ids): ?\Ess\M2ePro\Model\Listing\Product
    {
        $collection = $this->productCollectionFactory->createWithWalmartChildMode();
        $collection->addFieldToFilter(\Ess\M2ePro\Model\ResourceModel\Listing\Product::COLUMN_ID, ['in' => $ids]);

        /** @var \Ess\M2ePro\Model\Listing\Product $product */
        $product = $collection->getFirstItem();
        if ($product->isObjectNew()) {
            return null;
        }

        return $product;
    }

    public function save(\Ess\M2ePro\Model\Walmart\Listing\Product $product): void
    {
        $this->walmartProductResource->save($product);
    }
}
