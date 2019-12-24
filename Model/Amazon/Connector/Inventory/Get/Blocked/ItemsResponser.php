<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Blocked;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Blocked\ItemsResponser
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
        if (!empty($responseData['data']['skus']) && is_array($responseData['data']['skus'])) {
            $preparedData['data'] = $responseData['data']['skus'];
        }

        $this->preparedResponseData = $preparedData;
    }

    // ########################################
}
