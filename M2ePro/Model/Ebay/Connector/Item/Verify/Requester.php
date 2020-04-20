<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Verify;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Item\Verify\Requester
 */
class Requester extends \Ess\M2ePro\Model\Ebay\Connector\Item\Requester
{
    protected $isRealTime = true;

    //########################################

    protected function getCommand()
    {
        return ['item','add','single'];
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

    protected function isListingProductLocked()
    {
        return false;
    }

    protected function lockListingProduct()
    {
        return null;
    }

    protected function unlockListingProduct()
    {
        return null;
    }

    //----------------------------------------

    protected function getValidatorObject()
    {
        /** @var $obj \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\ListAction\Validator */
        $obj = parent::getValidatorObject();
        $obj->setIsVerifyCall(true);

        return $obj;
    }

    protected function makeRequestObject()
    {
        /** @var $obj \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\ListAction\Request */
        $obj = parent::makeRequestObject();
        $obj->setIsVerifyCall(true);

        return $obj;
    }

    public function getLogger()
    {
        $obj = parent::getLogger();
        $obj->setStoreMode(true);

        return $obj;
    }

    //########################################
}
