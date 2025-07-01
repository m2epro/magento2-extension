<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing\Channel;

class CreateCampaignOnChannel
{
    private \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
    }

    public function process(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        \Ess\M2ePro\Model\Ebay\Marketplace $ebayMarketplace,
        Dto\CreateCampaign $campaign
    ): \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\CreateConnectorResult {
        $connectorObj = $this->dispatcher->getConnectorByClass(
            \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\CreateConnector::class,
            [
                'account' => $ebayAccount->getServerHash(),
                'marketplace' => $ebayMarketplace->getParentObject()->getNativeId(),
                'campaign' => [
                    'name' => $campaign->getName(),
                    'start_date' => $campaign->getFormattedStartDate(),
                    'end_date' => $campaign->getFormattedEndDate(),
                    'rate' => $campaign->getRate(),
                ]
            ]
        );

        $this->dispatcher->process($connectorObj);

        return $connectorObj->getResponseData();
    }
}
