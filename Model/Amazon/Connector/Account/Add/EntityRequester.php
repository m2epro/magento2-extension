<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Account\Add;

class EntityRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    /**
     * @return array
     */
    protected function getRequestData(): array
    {
        return [
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
        return ['account','add','entity'];
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();
        if ((empty($responseData['hash']) || !isset($responseData['info'])) &&
            !$this->getResponse()->getMessages()->hasErrorEntities()
        ) {
            return false;
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    protected function prepareResponseData(): void
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
