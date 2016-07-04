<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Verify;

class SingleRequester extends \Ess\M2ePro\Model\Ebay\Connector\Item\Single\Requester
{
    //########################################

    protected function getCommand()
    {
        return array('item','add','single');
    }

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_LIST;
    }

    protected function getLogsAction()
    {
        return \Ess\M2ePro\Model\Listing\Log::ACTION_UNKNOWN;
    }

    //########################################

    public function process()
    {
        $this->setIsRealTime(true);
        $this->getLogger()->setStoreMode(true);

        parent::process();
    }

    //########################################

    protected function isListingProductLocked()
    {
        return false;
    }

    protected function lockListingProduct() {}

    protected function unlockListingProduct() {}

    //########################################

    protected function getRequestData()
    {
        if ($this->listingProduct->getChildObject()->isVariationsReady()) {
            $this->getRequestObject()->resetVariations();
        }

        $requestData = parent::getRequestData();
        $requestData['verify_call'] = true;

        return $requestData;
    }

    //########################################
}