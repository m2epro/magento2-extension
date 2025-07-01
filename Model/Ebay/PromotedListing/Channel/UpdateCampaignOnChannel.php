<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing\Channel;

class UpdateCampaignOnChannel
{
    private \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
    }

    public function process(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        Dto\UpdateCampaign $campaign
    ): \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\UpdateConnectorResult {
        $connectorObj = $this->dispatcher->getConnectorByClass(
            \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\UpdateConnector::class,
            [
                'account' => $ebayAccount->getServerHash(),
                'campaign' => [
                    'id' => $campaign->getId(),
                    'name' => $campaign->getName(),
                    'start_date' => $campaign->getFormattedStartDate(),
                    'end_date' => $campaign->getFormattedEndDate(),
                ]
            ]
        );

        $this->dispatcher->process($connectorObj);

        return $connectorObj->getResponseData();
    }
}
