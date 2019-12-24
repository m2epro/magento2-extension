<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization\General;

use \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Mass;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\General\RunParentProcessors
 */
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
        $listingProductCollection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('is_variation_parent', 1);
        $listingProductCollection->addFieldToFilter('variation_parent_need_processor', 1);
        $listingProductCollection->setPageSize(Mass::MAX_PROCESSORS_COUNT_PER_ONE_TIME);

        $listingsProducts = $listingProductCollection->getItems();

        if (empty($listingsProducts)) {
            return;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Walmart_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
        );
        $massProcessor->setListingsProducts($listingsProducts);

        $massProcessor->execute();
    }

    //########################################
}
