<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\Relist;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Product\Relist\Requester
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
        return \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST;
    }

    protected function getLogsAction()
    {
        if ($this->listingProduct->hasData('list_logs_action')) {
            return $this->listingProduct->getData('list_logs_action');
        }

        return \Ess\M2ePro\Model\Listing\Log::ACTION_RELIST_PRODUCT_ON_COMPONENT;
    }

    protected function getLogsActionId()
    {
        if ($this->listingProduct->hasData('list_logs_action_id')) {
            return $this->listingProduct->getData('list_logs_action_id');
        }

        return parent::getLogsActionId();
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

    public function eventBeforeExecuting()
    {
        if ($this->listingProduct->hasData('is_list_action')) {
            $additionalData = $this->listingProduct->getAdditionalData();
            $additionalData['is_list_action'] = true;
            $this->listingProduct->setSettings('additional_data', $additionalData);
            $this->listingProduct->save();
        }

        return parent::eventBeforeExecuting();
    }

    // ########################################
}
