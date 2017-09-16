<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\General;

use \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Mass;

class RunParentProcessors extends AbstractModel
{
    //########################################

    protected function getNick()
    {
        return '/run_parent_processors/';
    }

    protected function getTitle()
    {
        return 'Update Variation Parent Listing Products';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    // ---------------------------------------

    protected function intervalIsEnabled()
    {
        return true;
    }

    //########################################

    protected function performActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('is_variation_parent', 1);
        $listingProductCollection->addFieldToFilter('variation_parent_need_processor', 1);
        $listingProductCollection->setPageSize(Mass::MAX_PROCESSORS_COUNT_PER_ONE_TIME);

        $listingsProducts = $listingProductCollection->getItems();

        if (empty($listingsProducts)) {
            return;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Mass'
        );
        $massProcessor->setListingsProducts($listingsProducts);

        $massProcessor->execute();
    }

    //########################################
}