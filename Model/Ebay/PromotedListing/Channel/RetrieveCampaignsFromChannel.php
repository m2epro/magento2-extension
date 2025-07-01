<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\PromotedListing\Channel;

class RetrieveCampaignsFromChannel
{
    private \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
    }

    public function process(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        int $necessaryPage
    ): \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\ListConnectorResult {
        $connectorObj = $this->dispatcher->getConnectorByClass(
            \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\ListConnector::class,
            [
                'account' => $ebayAccount->getServerHash(),
                'necessary_page' => $necessaryPage,
            ]
        );

        $this->dispatcher->process($connectorObj);

        return $connectorObj->getResponseData();
    }
}
