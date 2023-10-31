<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category\Specific\Validation;

class Validate extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;
    /** @var \Ess\M2ePro\Model\Ebay\Category\SpecificValidator */
    private $ebayCategorySpecificValidator;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\Ebay\Category\SpecificValidator $ebayCategorySpecificValidator,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
        $this->ebayCategorySpecificValidator = $ebayCategorySpecificValidator;
    }

    public function execute()
    {
        $listingProductsIdsString = $this->getRequest()->getParam('listing_product_ids');
        if (empty($listingProductsIdsString)) {
            return $this->getResult();
        }

        $listingProductsIds = explode(',', $listingProductsIdsString);

        $collection = $this->listingProductCollectionFactory->createWithEbayChildMode();
        $collection->addFieldToFilter('main_table.id', ['in' => $listingProductsIds]);
        $collection->addFieldToFilter('second_table.template_category_id', ['notnull' => true]);

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($collection->getItems() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
            $ebayListingProduct = $listingProduct->getChildObject();
            $this->ebayCategorySpecificValidator->validate($ebayListingProduct);
        }

        return $this->getResult();
    }
}
