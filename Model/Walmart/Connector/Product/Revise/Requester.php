<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\Revise;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Product\Revise\Requester
 */
class Requester extends \Ess\M2ePro\Model\Walmart\Connector\Product\Requester
{
    // ########################################

    public function getCommand()
    {
        return ['product', 'update', 'entities'];
    }

    // ########################################

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
    }

    protected function getLogsAction()
    {
        return \Ess\M2ePro\Model\Listing\Log::ACTION_REVISE_PRODUCT_ON_COMPONENT;
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
            if (!$childListingProduct->isListed() || $childListingProduct->isBlocked()) {
                continue;
            }

            if (!$childListingProduct->isRevisable()) {
                continue;
            }

            $resultListingProducts[] = $childListingProduct;
        }

        return $resultListingProducts;
    }

    // ########################################
}
