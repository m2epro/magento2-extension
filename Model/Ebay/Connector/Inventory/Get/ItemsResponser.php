<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Inventory\Get;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Inventory\Get\ItemsResponser
 */
abstract class ItemsResponser extends \Ess\M2ePro\Model\Ebay\Connector\Command\Pending\Responser
{
    // ########################################

    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        if (!isset($responseData['items']) || !isset($responseData['to_time'])) {
            return false;
        }

        return true;
    }

    // ########################################
}
