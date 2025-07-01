<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing\Channel;

class AddItemsToCampaignOnChannel
{
    private \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
    }

    public function process(
        \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign $campaign,
        array $ebayItemIds
    ): \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\AddItemsConnectorResult {

        $items = [];
        foreach ($ebayItemIds as $ebayItemId) {
            $items[] = [
                'id' => $ebayItemId,
                'rate' => $campaign->getRate(),
            ];
        }

        $connectorObj = $this->dispatcher->getConnectorByClass(
            \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\AddItemsConnector::class,
            [
                'account' => $campaign->getEbayAccount()->getServerHash(),
                'campaign_id' => $campaign->getEbayCampaignId(),
                'items' => $items
            ]
        );

        $this->dispatcher->process($connectorObj);

        return $connectorObj->getResponseData();
    }
}
