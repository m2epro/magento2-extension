<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Defected;

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
        $preparedData = array(
            'data' => array(),
        );

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