<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category\Specific\Validation\Modal;

class Open extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    /** @var \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\Specific\Validation\Modal\ListingProductStorage */
    private $storage;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\Specific\Validation\Modal\ListingProductStorage $storage,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);
        $this->storage = $storage;
        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
    }

    public function execute()
    {
        $listingProductIdsString = $this->getRequest()->getParam('listing_product_ids');

        if ($listingProductIdsString === null) {
            $listingProductIds = $this->storage->getListingProductIds();
        } else {
            $listingProductIds = explode(',', $listingProductIdsString);
            $listingProductIds = $this->filterListingProductIds($listingProductIds);
            $this->storage->setListingProductIds($listingProductIds);
        }

        if (empty($listingProductIds) && $this->isAjax()) {
            return $this->getResult();
        }

        $productTypeValidationGrid = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Ebay\Category\Specific\Validation\Grid::class,
            '',
            ['listingProductIds' => $listingProductIds]
        );

        $this->isAjax()
            ? $this->setAjaxContent($productTypeValidationGrid)
            : $this->addContent($productTypeValidationGrid);

        return $this->getResult();
    }

    private function filterListingProductIds(array $listingProductIds): array
    {
        $collection = $this->listingProductCollectionFactory->createWithEbayChildMode();
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->addFieldToSelect('id', 'id');
        $collection->addFieldToFilter('id', ['in' => $listingProductIds]);

        return $collection->getConnection()->fetchCol($collection->getSelect());
    }
}
