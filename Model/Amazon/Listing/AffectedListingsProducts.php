<?php

namespace Ess\M2ePro\Model\Amazon\Listing;

class AffectedListingsProducts extends \Ess\M2ePro\Model\Template\AffectedListingsProductsAbstract
{
    protected \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory;

    // ----------------------------------------

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->amazonFactory = $amazonFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory, $data);
    }

    // ----------------------------------------

    public function loadCollection(array $filters = [])
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter(
            \Ess\M2ePro\Model\ResourceModel\Listing\Product::LISTING_ID_FIELD,
            ['eq' => (int)$this->model->getId()]
        );

        if (!empty($filters['only_physical_units'])) {
            $listingProductCollection->addFieldToFilter(
                \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product::COLUMN_IS_VARIATION_PARENT,
                ['eq' => 0]
            );
        }

        if (!empty($filters['template_shipping_id'])) {
            $listingProductCollection->addFieldToFilter(
                \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product::COLUMN_TEMPLATE_SHIPPING_ID,
                [
                    ['null' => true],
                    ['eq' => 0],
                ]
            );
        }

        return $listingProductCollection;
    }
}
