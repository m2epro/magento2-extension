<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category\Specific\Validation;

class GetListingProductIdsByCategoryId extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product */
    private $ebayListingProductResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product $ebayListingProductResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);
        $this->ebayListingProductResource = $ebayListingProductResource;
    }

    public function execute()
    {
        $templateCategoryId = $this->getRequest()->getParam('template_category_id');

        if ($templateCategoryId === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Parameter template_category_id is required');
        }

        $this->setJsonContent($this->getListingProductIds((int)$templateCategoryId));

        return $this->getResult();
    }

    private function getListingProductIds(int $productTypeId): array
    {
        $select = $this->ebayListingProductResource->getConnection()->select();
        $select->from($this->ebayListingProductResource->getMainTable(), ['listing_product_id']);
        $select->where('template_category_id = ?', $productTypeId);

        return $this->ebayListingProductResource->getConnection()->fetchCol($select);
    }
}
