<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign;

class UpdateConnector extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    public function getResponseData(): UpdateConnectorResult
    {
        return $this->responseData;
    }

    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'campaign' => $this->params['campaign'],
        ];
    }

    protected function getCommand(): array
    {
        return ['promotedListing', 'campaign', 'update'];
    }

    protected function prepareResponseData(): void
    {
        $errorMessages = $this->getResponse()->getMessages()->getErrorEntities();
        if (count($errorMessages) > 0) {
            $this->responseData = UpdateConnectorResult::createFail($errorMessages);

            return;
        }

        $this->responseData = UpdateConnectorResult::createSuccess();
    }
}
