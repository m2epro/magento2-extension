<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\ProductType\Validation;

class Validate extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\Amazon\ProductType\AttributesValidator */
    private $productTypeAttributesValidator;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\Amazon\ProductType\AttributesValidator $productTypeAttributesValidator,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->productTypeAttributesValidator = $productTypeAttributesValidator;
    }

    public function execute()
    {
        $listingProductsIdsString = $this->getRequest()->getParam('listing_product_ids');
        $listingProductsIds = explode(',', $listingProductsIdsString);

        $collection = $this->listingProductCollectionFactory->createWithAmazonChildMode();
        $collection->addFieldToFilter('main_table.id', ['in' => $listingProductsIds]);
        $collection->addFieldToFilter('second_table.template_product_type_id', ['notnull' => true]);

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($collection->getItems() as $listingProduct) {
            $amazonListingProduct = $listingProduct->getChildObject();
            $productTypeId = $amazonListingProduct->getTemplateProductTypeId();
            $this->productTypeAttributesValidator
                ->validate($amazonListingProduct, $productTypeId);
        }

        return $this->getResult();
    }
}
