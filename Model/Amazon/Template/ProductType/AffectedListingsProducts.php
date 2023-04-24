<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\ProductType;

class AffectedListingsProducts extends \Ess\M2ePro\Model\Template\AffectedListingsProductsAbstract
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory */
    private $listingProductCollectionFactory;

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Listing\Product\CollectionFactory $listingProductCollectionFactory
     * @param array $data
     */
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

    /**
     * @inheridoc
     */
    public function loadCollection(array $filters = [])
    {
        $listingProductCollection = $this->listingProductCollectionFactory->create([
            'childMode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
        ]);
        $listingProductCollection->addFieldToFilter('template_product_type_id', $this->model->getId());

        return $listingProductCollection;
    }
}
