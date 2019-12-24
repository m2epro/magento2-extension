<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Defected;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Defected\ItemsResponser
 */
abstract class ItemsResponser extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Responser
{
    // ########################################

    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();
        return isset($responseData['data']);
    }

    protected function prepareResponseData()
    {
        $preparedData = [
            'data' => [],
        ];

        $responseData = $this->getResponse()->getResponseData();

        foreach ($responseData['data'] as $receivedItem) {
            if (empty($receivedItem['sku'])) {
                continue;
            }

            $preparedData['data'][$receivedItem['sku']] = $receivedItem;
        }

        $this->preparedResponseData = $preparedData;
    }

    // ########################################
}
