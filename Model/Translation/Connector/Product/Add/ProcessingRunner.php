<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Translation\Connector\Product\Add;

class ProcessingRunner extends \Ess\M2ePro\Model\Connector\Command\Pending\Processing\Runner\Single
{
    const MAX_LIFETIME = 907200;
    const PENDING_REQUEST_MAX_LIFE_TIME = 864000;

    // ##################################

    /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
    protected $listingsProducts = array();

    protected $ebayFactory;

    // ##################################

    function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->ebayFactory = $ebayFactory;
        parent::__construct($parentFactory, $activeRecordFactory, $helperFactory, $modelFactory);
    }

    // ##################################

    protected function setLocks()
    {
        parent::setLocks();

        $alreadyLockedListings = array();
        foreach ($this->getListingsProducts() as $listingProduct) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            $listingProduct->addProcessingLock(NULL, $this->getProcessingObject()->getId());
            $listingProduct->addProcessingLock('in_action', $this->getProcessingObject()->getId());
            $listingProduct->addProcessingLock('translation_action', $this->getProcessingObject()->getId());

            if (isset($alreadyLockedListings[$listingProduct->getListingId()])) {
                continue;
            }

            $listingProduct->getListing()->addProcessingLock(NULL, $this->getProcessingObject()->getId());

            $alreadyLockedListings[$listingProduct->getListingId()] = true;
        }
    }

    protected function unsetLocks()
    {
        parent::unsetLocks();

        $alreadyUnlockedListings = array();
        foreach ($this->getListingsProducts() as $listingProduct) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            $listingProduct->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());
            $listingProduct->deleteProcessingLocks('in_action', $this->getProcessingObject()->getId());
            $listingProduct->deleteProcessingLocks('translation_action', $this->getProcessingObject()->getId());

            if (isset($alreadyUnlockedListings[$listingProduct->getListingId()])) {
                continue;
            }

            $listingProduct->getListing()->deleteProcessingLocks(NULL, $this->getProcessingObject()->getId());

            $alreadyUnlockedListings[$listingProduct->getListingId()] = true;
        }
    }

    // ##################################

    protected function getListingsProducts()
    {
        if (!empty($this->listingsProducts)) {
            return $this->listingsProducts;
        }

        $params = $this->getParams();

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('id', array('in' => $params['listing_product_ids']));

        return $this->listingsProducts = $collection->getItems();
    }

    // ##################################
}