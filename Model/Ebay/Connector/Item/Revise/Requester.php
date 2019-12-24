<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Revise;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Item\Revise\Requester
 */
class Requester extends \Ess\M2ePro\Model\Ebay\Connector\Item\Requester
{
    //########################################

    protected function getCommand()
    {
        return ['item','update','revise'];
    }

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
    }

    protected function getLogsAction()
    {
        return \Ess\M2ePro\Model\Listing\Log::ACTION_REVISE_PRODUCT_ON_COMPONENT;
    }

    //########################################

    public function process()
    {
        $this->initOutOfStockControlLogic();
        parent::process();
    }

    //########################################

    protected function initOutOfStockControlLogic()
    {
        $ebayListingProduct = $this->listingProduct->getChildObject();

        $outOfStockControlCurrentState = $ebayListingProduct->getOutOfStockControl();
        $outOfStockControlTemplateState = $ebayListingProduct->getEbaySellingFormatTemplate()
            ->getOutOfStockControl();

        if (!$outOfStockControlCurrentState && $outOfStockControlTemplateState) {
            $outOfStockControlCurrentState = true;
        }

        $this->params['out_of_stock_control_current_state'] = $outOfStockControlCurrentState;
        $this->params['out_of_stock_control_result'] = $outOfStockControlCurrentState
            || $ebayListingProduct->getEbayAccount()
                ->getOutOfStockControl();
    }

    //########################################
}
