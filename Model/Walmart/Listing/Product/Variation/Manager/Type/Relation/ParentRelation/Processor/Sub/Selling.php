<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

class Selling extends AbstractModel
{
    //########################################

    protected function check()
    {
    }

    protected function execute()
    {
        $qty = null;
        $price = null;

        $totalCount = 0;

        foreach ($this->getProcessor()->getTypeModel()->getChildListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
            $walmartListingProduct = $listingProduct->getChildObject();

            if ($listingProduct->isNotListed() ||
                ($listingProduct->isBlocked() && !$walmartListingProduct->isOnlinePriceInvalid())) {
                continue;
            }

            $totalCount++;

            $qty = (int)$qty + (int)$walmartListingProduct->getOnlineQty();

            $actualOnlinePrice = $walmartListingProduct->getOnlinePrice();

            if (!is_null($actualOnlinePrice) && (is_null($price) || $price > $actualOnlinePrice)) {
                $price = $actualOnlinePrice;
            }
        }

        $this->getProcessor()->getListingProduct()->getChildObject()->addData(array(
            'online_qty'   => $qty,
            'online_price' => $price,
        ));
    }

    //########################################
}