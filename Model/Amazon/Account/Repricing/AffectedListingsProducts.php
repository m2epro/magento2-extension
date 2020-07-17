<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Account\Repricing;

/**
 * Class \Ess\M2ePro\Model\Amazon\Account\Repricing\AffectedListingsProducts
 */
class AffectedListingsProducts extends \Ess\M2ePro\Model\Template\AffectedListingsProductsAbstract
{
    protected $amazonFactory;

    //########################################

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

    //########################################

    public function loadCollection(array $filters = [])
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Collection $listingCollection */
        $listingCollection = $this->amazonFactory->getObject('Listing')->getCollection();
        $listingCollection->addFieldToFilter('account_id', $this->model->getAccountId());
        $listingCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $listingCollection->getSelect()->columns('id');

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('listing_id', ['in' => $listingCollection->getSelect()]);
        $listingProductCollection->addFieldToFilter('is_variation_parent', 0);
        $listingProductCollection->addFieldToFilter('is_repricing', 1);

        return $listingProductCollection;
    }

    //########################################
}
