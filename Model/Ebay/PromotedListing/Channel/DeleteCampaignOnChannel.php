<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing\Channel;

class DeleteCampaignOnChannel
{
    private \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
    }

    public function process(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        string $ebayCampaignId
    ): \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\DeleteConnectorResult {
        $connectorObj = $this->dispatcher->getConnectorByClass(
            \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\DeleteConnector::class,
            [
                'account' => $ebayAccount->getServerHash(),
                'campaign_id' => $ebayCampaignId
            ]
        );

        $this->dispatcher->process($connectorObj);

        return $connectorObj->getResponseData();
    }
}
