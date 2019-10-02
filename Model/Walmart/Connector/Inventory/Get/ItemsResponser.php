<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Inventory\Get;

/**
 * Class ItemsResponser
 * @package Ess\M2ePro\Model\Walmart\Connector\Inventory\Get
 */
abstract class ItemsResponser extends \Ess\M2ePro\Model\Walmart\Connector\Command\Pending\Responser
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
            if (empty($receivedItem['wpid'])) {
                continue;
            }

            $preparedData['data'][$receivedItem['wpid']] = $receivedItem;
        }

        $this->preparedResponseData = $preparedData;
    }

    // ########################################
}
