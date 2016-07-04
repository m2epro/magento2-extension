<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Stop;

class MultipleRequester extends \Ess\M2ePro\Model\Ebay\Connector\Item\Multiple\Requester
{
    //########################################

    public function getMaxProductsCount()
    {
        return 10;
    }

    //########################################

    protected function getCommand()
    {
        return array('item','update','ends');
    }

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_STOP;
    }

    protected function getLogsAction()
    {
        if (!empty($this->params['remove'])) {
            return \Ess\M2ePro\Model\Listing\Log::ACTION_STOP_AND_REMOVE_PRODUCT;
        }

        return \Ess\M2ePro\Model\Listing\Log::ACTION_STOP_PRODUCT_ON_COMPONENT;
    }

    //########################################

    protected function getRequestData()
    {
        $requestData = parent::getRequestData();

        $requestData['items'] = $requestData['products'];
        unset($requestData['products']);

        return $requestData;
    }

    //########################################
}