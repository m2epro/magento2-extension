<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Get;

class AuthUrlRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    protected function getRequestData(): array
    {
        return [
            'back_url' => $this->params['back_url'],
            'marketplace' => $this->params['marketplace_native_id'],
        ];
    }

    protected function getCommand(): array
    {
        return ['account', 'get', 'authUrl'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return !empty($responseData['url']);
    }

    protected function prepareResponseData(): void
    {
        $responseData = $this->getResponse()->getResponseData();

        $this->responseData = [
            'url' => $responseData['url']
        ];
    }

    /**
     * @return string
     */
    public function getAuthUrlFromResponseData(): string
    {
        return $this->responseData['url'];
    }
}
