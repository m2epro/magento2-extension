<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\ListAction\UpdateInventory;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Product\ListAction\UpdateInventory\Requester
 */
class Requester extends \Ess\M2ePro\Model\Walmart\Connector\Product\Requester
{
    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Walmart_Listing_Product_Action_ListAction_ProcessingRunner';
    }

    //########################################

    public function getCommand()
    {
        return ['product','update','entities'];
    }

    //########################################

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST;
    }

    protected function getLogsAction()
    {
        return \Ess\M2ePro\Model\Listing\Log::ACTION_RELIST_PRODUCT_ON_COMPONENT;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    protected function filterChildListingProductsByStatus(array $listingProducts)
    {
        $resultListingProducts = [];

        foreach ($listingProducts as $childListingProduct) {
            if (!$childListingProduct->isNotListed() || !$childListingProduct->isListable()) {
                continue;
            }

            $resultListingProducts[] = $childListingProduct;
        }

        return $resultListingProducts;
    }

    //########################################
}
