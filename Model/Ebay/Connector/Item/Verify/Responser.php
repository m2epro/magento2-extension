<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Verify;

class Responser extends \Ess\M2ePro\Model\Ebay\Connector\Item\Responser
{
    //########################################

    protected function getSuccessfulMessage()
    {
        return NULL;
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

    protected function processResponseData() {}

    //########################################
}