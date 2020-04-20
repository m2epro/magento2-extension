<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Listing\Product\Delete;

/**
 * Class \Ess\M2ePro\Observer\Listing\Product\Delete\Before
 */

class Before extends \Ess\M2ePro\Observer\AbstractModel
{
    //########################################

    public function process()
    {
        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        $listingProduct = $this->getEvent()->getData('object');

        /** @var \Ess\M2ePro\Model\Listing\Product\Indexer\VariationParent\Manager $manager */
        $manager = $this->modelFactory->getObject('Listing_Product_Indexer_VariationParent_Manager', [
            'listing' => $listingProduct->getListing()
        ]);
        $manager->markInvalidated();
    }

    //########################################
}
