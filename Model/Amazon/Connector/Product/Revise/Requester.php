<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Revise;

class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Product\Requester
{
    // ########################################

    public function getCommand()
    {
        return array('product','update','entities');
    }

    // ########################################

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
    }

    protected function getLockIdentifier()
    {
        if (!empty($this->params['switch_to'])) {
            $switchTo = $this->params['switch_to'];
            if ($switchTo === \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_AFN) {
                return 'switch_to_afn';
            }
            if ($switchTo === \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_MFN) {
                return 'switch_to_mfn';
            }
        }

        return parent::getLockIdentifier();
    }

    protected function getLogsAction()
    {
        if (!empty($this->params['switch_to'])) {
            $switchTo = $this->params['switch_to'];
            if ($switchTo === \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_AFN) {
                return \Ess\M2ePro\Model\Listing\Log::ACTION_SWITCH_TO_AFN_ON_COMPONENT;
            }
            if ($switchTo === \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\Qty::FULFILLMENT_MODE_MFN) {
                return \Ess\M2ePro\Model\Listing\Log::ACTION_SWITCH_TO_MFN_ON_COMPONENT;
            }
        }

        return \Ess\M2ePro\Model\Listing\Log::ACTION_REVISE_PRODUCT_ON_COMPONENT;
    }

    // ########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    protected function filterChildListingProductsByStatus(array $listingProducts)
    {
        $resultListingProducts = array();

        foreach ($listingProducts as $childListingProduct) {

            if (!$childListingProduct->getChildObject()->isAfnChannel() &&
                (!$childListingProduct->isListed() || $childListingProduct->isBlocked())) {
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