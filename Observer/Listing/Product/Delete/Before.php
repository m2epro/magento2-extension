<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Listing\Product\Delete;

class Before extends \Ess\M2ePro\Observer\AbstractModel
{
    //########################################

    public function process()
    {
        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        $listingProduct = $this->getEvent()->getData('object');

        /** @var \Ess\M2ePro\Model\Indexer\Listing\Product\VariationParent\Manager $manager */
        $manager = $this->modelFactory->getObject('Indexer\Listing\Product\VariationParent\Manager');
        $manager->setListing($listingProduct->getListing());
        $manager->markInvalidated();
    }

    //########################################
}