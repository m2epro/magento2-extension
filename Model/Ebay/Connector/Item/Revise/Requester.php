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
        return ['item','update','reviseManager'];
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
}
