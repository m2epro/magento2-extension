<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Stop;

class Requester extends \Ess\M2ePro\Model\Ebay\Connector\Item\Requester
{
    //########################################

    protected function getCommand()
    {
        return array('item','update','end');
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

    public function getRequestTimeout()
    {
        return self::DEFAULT_REQUEST_TIMEOUT;
    }

    //########################################

    public function initializeVariations() {}

    //########################################
}