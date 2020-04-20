<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product;

use Ess\M2ePro\Helper\Component\Walmart;
use \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Mass
    as MassProcessor;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\RunVariationParentProcessors
 */
class RunVariationParentProcessors extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/listing/product/run_variation_parent_processors';

    //####################################

    protected function performActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->parentFactory->getObject(Walmart::NICK, 'Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('is_variation_parent', 1);
        $listingProductCollection->addFieldToFilter('variation_parent_need_processor', 1);
        $listingProductCollection->setPageSize(MassProcessor::MAX_PROCESSORS_COUNT_PER_ONE_TIME);

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
