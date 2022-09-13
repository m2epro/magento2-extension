<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\AfnQty;

abstract class ItemsResponser extends \Ess\M2ePro\Model\Connector\Command\Pending\Responser
{
    /**
     * @return bool
     */
    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();
        return isset($responseData['data']);
    }
}
