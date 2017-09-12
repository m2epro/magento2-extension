<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\ListAction;

class Requester extends \Ess\M2ePro\Model\Ebay\Connector\Item\Requester
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
        return \Ess\M2ePro\Model\Listing\Log::ACTION_LIST_PRODUCT_ON_COMPONENT;
    }

    //########################################
}