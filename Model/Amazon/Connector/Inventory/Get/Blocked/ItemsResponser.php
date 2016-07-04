<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Blocked;

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
        if (!empty($responseData['data']['skus']) && is_array($responseData['data']['skus'])) {
            $preparedData['data'] = $responseData['data']['skus'];
        }

        $this->preparedResponseData = $preparedData;
    }

    // ########################################
}