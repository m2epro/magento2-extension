<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\ProductType;

class GetListingProductIdsByProductType extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product */
    private $amazonListingProductResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $amazonListingProductResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->amazonListingProductResource = $amazonListingProductResource;
    }

    public function execute()
    {
        $productTypeId = $this->getRequest()->getParam('product_type_id');

        if ($productTypeId === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Parameter product_type_id is required');
        }

        $this->setJsonContent($this->getListingProductIds((int)$productTypeId));

        return $this->getResult();
    }

    private function getListingProductIds(int $productTypeId)
    {
        $select = $this->amazonListingProductResource->getConnection()->select();
        $select->from($this->amazonListingProductResource->getMainTable(), ['listing_product_id']);
        $select->where('template_product_type_id = ?', $productTypeId);

        return $this->amazonListingProductResource->getConnection()->fetchCol($select);
    }
}
