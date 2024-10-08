<?php

namespace Ess\M2ePro\Model\Walmart\ProductType\Builder;

class AffectedListingsProducts extends \Ess\M2ePro\Model\Template\AffectedListingsProductsAbstract
{
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory,
        array $data = []
    ) {
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory, $data);

        $this->listingProductCollectionFactory = $listingProductCollectionFactory;
    }

    public function loadCollection(array $filters = [])
    {
        $listingProductCollection = $this->listingProductCollectionFactory->create([
            'childMode' => \Ess\M2ePro\Helper\Component\Walmart::NICK,
        ]);
        $listingProductCollection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product::COLUMN_PRODUCT_TYPE_ID,
            $this->model->getId()
        );

        return $listingProductCollection;
    }
}
