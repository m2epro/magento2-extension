<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Verify;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Item\Verify\Responser
 */
class Responser extends \Ess\M2ePro\Model\Ebay\Connector\Item\Responser
{
    //########################################

    protected function getSuccessfulMessage()
    {
        return null;
    }

    //########################################

    protected function processResponseMessages()
    {
        $this->getLogger()->setStoreMode(true);
        parent::processResponseMessages();
    }

    protected function prepareResponseData()
    {
        $responseData = $this->getResponse()->getResponseData();

        if (isset($responseData['ebay_item_fees']) && is_array($responseData['ebay_item_fees'])) {
            $this->preparedResponseData = $responseData['ebay_item_fees'];
        }
    }

    protected function processResponseData()
    {
        return null;
    }

    //########################################
}
