<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist\Request getRequestObject()
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Relist;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Product\Relist\Requester
 */
class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Product\Requester
{
    // ########################################

    public function getCommand()
    {
        return ['product','update','entities'];
    }

    // ########################################

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST;
    }

    protected function getLogsAction()
    {
        return \Ess\M2ePro\Model\Listing\Log::ACTION_RELIST_PRODUCT_ON_COMPONENT;
    }

    // ########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    protected function filterChildListingProductsByStatus(array $listingProducts)
    {
        $resultListingProducts = [];

        foreach ($listingProducts as $childListingProduct) {
            if (!$childListingProduct->isStopped() || !$childListingProduct->isRelistable()) {
                continue;
            }

            $resultListingProducts[] = $childListingProduct;
        }

        return $resultListingProducts;
    }

    // ########################################
}
