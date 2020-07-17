<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\AffectedListingsProducts
 */
class AffectedListingsProducts extends \Ess\M2ePro\Model\Template\AffectedListingsProductsAbstract
{
    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @inheritDoc
     */
    public function loadCollection(array $filters = [])
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('listing_id', $this->model->getId());

        if (!empty($filters['only_physical_units'])) {
            $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        }

        return $listingProductCollection;
    }

    //########################################
}
