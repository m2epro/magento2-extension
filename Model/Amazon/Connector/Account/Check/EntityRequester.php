<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Check;

class EntityRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account_server_hash'],
        ];
    }

    /**
     * @return array
     */
    protected function getCommand(): array
    {
        return ['account', 'check', 'entity'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['status']) && array_key_exists('explanation', $responseData);
    }

    protected function prepareResponseData(): void
    {
        $response = $this->getResponse()->getResponseData();

        $this->responseData = [
            'status'      => $response['status'],
            'explanation' => $response['explanation'] === null ? '' : $response['explanation']
        ];
    }
}
