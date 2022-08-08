<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Update;

class EntityRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    /**
     * @return array
     */
    protected function getRequestData(): array
    {
        return [
            'account'        => $this->params['account_server_hash'],
            'merchant_id'    => $this->params['merchant_id'],
            'token'          => $this->params['token'],
            'marketplace_id' => $this->params['marketplace_id'],
        ];
    }

    /**
     * @return array
     */
    protected function getCommand(): array
    {
        return ['account', 'update', 'entity'];
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();
        if (!isset($responseData['info']) && !$this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    protected function processResponseData(): void
    {
        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {
            if (!$message->isError()) {
                continue;
            }

            throw new \Exception($message->getText());
        }

        $this->responseData = $this->getResponse()->getResponseData();
    }
}
