<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\ProductType;

class GetListingProductIdsByProductType extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Main
{
    private \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product $walmartListingProductResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product $walmartListingProductResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->walmartListingProductResource = $walmartListingProductResource;
    }

    public function execute()
    {
        $productTypeId = $this->getRequest()->getParam('product_type_id');
        if ($productTypeId === null) {
            throw new \LogicException('Parameter product_type_id is required');
        }

        $this->setJsonContent($this->getListingProductIds((int)$productTypeId));

        return $this->getResult();
    }

    private function getListingProductIds(int $productTypeId)
    {
        $select = $this->walmartListingProductResource->getConnection()->select();
        $select->from($this->walmartListingProductResource->getMainTable(), ['listing_product_id']);
        $select->where(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_PRODUCT_TYPE_ID . ' = ?',
            $productTypeId
        );

        return $this->walmartListingProductResource->getConnection()->fetchCol($select);
    }
}
