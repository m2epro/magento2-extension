<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Check;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Account\Check\EntityRequester
 */
class EntityRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    /**
     * @return array
     */
    protected function getRequestData()
    {
        return [
            'account' => $this->params['account_server_hash'],
        ];
    }

    /**
     * @return array
     */
    protected function getCommand()
    {
        return ['account', 'check', 'entity'];
    }

    protected function validateResponse()
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['status']) && array_key_exists('explanation', $responseData);
    }

    protected function prepareResponseData()
    {
        $response = $this->getResponse()->getResponseData();

        $this->responseData = [
            'status'      => $response['status'],
            'explanation' => $response['explanation'] === null ? '' : $response['explanation']
        ];
    }
}
