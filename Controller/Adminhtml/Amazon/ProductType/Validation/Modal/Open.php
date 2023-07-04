<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\ProductType\Validation\Modal;

class Open extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Main
{
    /** @var \Ess\M2ePro\Controller\Adminhtml\Amazon\ProductType\Validation\Modal\ListingProductStorage */
    private $storage;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Controller\Adminhtml\Amazon\ProductType\Validation\Modal\ListingProductStorage $storage,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
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
            \Ess\M2ePro\Block\Adminhtml\Amazon\ProductType\Validate\Grid::class,
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
        $collection = $this->listingProductCollectionFactory->createWithAmazonChildMode();
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->addFieldToSelect('id', 'id');
        $collection->addFieldToFilter('template_product_type_id', ['notnull' => true]);
        $collection->addFieldToFilter('status', [
            'eq' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
        ]);
        $collection->addFieldToFilter('id', ['in' => $listingProductIds]);

        return $collection->getConnection()->fetchCol($collection->getSelect());
    }
}
