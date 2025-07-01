<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign;

class DeleteConnector extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    public function getResponseData(): DeleteConnectorResult
    {
        return $this->responseData;
    }

    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'campaign_id' => $this->params['campaign_id'],
        ];
    }

    protected function getCommand(): array
    {
        return ['promotedListing', 'campaign', 'delete'];
    }

    protected function prepareResponseData(): void
    {
        $errorMessages = $this->getResponse()->getMessages()->getErrorEntities();
        if (count($errorMessages) > 0) {
            $this->responseData = DeleteConnectorResult::createFail($errorMessages);

            return;
        }

        $this->responseData = DeleteConnectorResult::createSuccess();
    }
}
