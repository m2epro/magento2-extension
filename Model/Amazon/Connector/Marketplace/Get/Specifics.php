<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Marketplace\Get;

class Specifics extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    /**
     * @return array
     */
    protected function getRequestData(): array
    {
        return [
            'marketplace' => $this->params['marketplace'],
            'product_type_nick' => $this->params['product_type_nick'],
        ];
    }

    /**
     * @return array
     */
    protected function getCommand(): array
    {
        return ['marketplace', 'get', 'specifics'];
    }

    /**
     * @return bool
     */
    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return array_key_exists('specifics', $responseData)
            && (
                is_array($responseData['specifics'])
                || $responseData['specifics'] === null
            );
    }

    /**
     * @throws \Exception
     */
    protected function prepareResponseData(): void
    {
        $responseData = $this->getResponse()->getResponseData();
        if (!is_array($responseData['specifics'])) {
            $responseData['specifics'] = [];
        }

        $this->responseData = $responseData;
    }
}
