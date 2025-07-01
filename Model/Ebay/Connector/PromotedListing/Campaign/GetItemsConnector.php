<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign;

class GetItemsConnector extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    public function getResponseData(): GetItemsConnectorResult
    {
        return $this->responseData;
    }

    protected function getRequestData(): array
    {
        return [
            'account' => $this->params['account'],
            'campaign_id' => $this->params['campaign_id'],
            'necessary_page' => $this->params['necessary_page'],
        ];
    }

    protected function getCommand(): array
    {
        return ['promotedListing', 'campaign', 'getItems'];
    }

    protected function validateResponse(): bool
    {
        $responseData = $this->getResponse()->getResponseData();

        return isset($responseData['items']);
    }

    protected function prepareResponseData(): void
    {
        $responseData = $this->getResponse()->getResponseData();

        $channelCampaignItems = [];
        foreach ($responseData['items'] as $item) {
            $channelCampaignItems[] = new \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\CampaignItem(
                (string)$item['id'],
                (float)$item['rate']
            );
        }

        $this->responseData = new GetItemsConnectorResult(
            $channelCampaignItems,
            (int)$responseData['next_page']
        );
    }
}
