<?php

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

class Template extends AbstractModel
{
    protected function check()
    {
        return null;
    }

    protected function execute()
    {
        $productTypeId = $this->getProcessor()->getWalmartListingProduct()->getProductTypeId();

        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();
            if ($walmartListingProduct->getProductTypeId() != $productTypeId) {
                $walmartListingProduct->setData(
                    \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_PRODUCT_TYPE_ID,
                    $productTypeId
                );
                $walmartListingProduct->save();
            }
        }
    }
}
