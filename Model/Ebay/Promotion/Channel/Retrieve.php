<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion\Channel;

class Retrieve
{
    private \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
    }

    public function getChannelPromotions(
        \Ess\M2ePro\Model\Ebay\Account $ebayAccount,
        \Ess\M2ePro\Model\Marketplace $marketplace
    ): \Ess\M2ePro\Model\Ebay\Promotion\Channel\PromotionCollection {
        /** @var \Ess\M2ePro\Model\Ebay\Connector\Promotion\Get\ItemsConnector $connectorObj */
        $connectorObj = $this->dispatcher->getConnector(
            'Promotion',
            'Get',
            'ItemsConnector',
            [
                'account' => $ebayAccount->getServerHash(),
                'marketplace' => $marketplace->getNativeId(),
            ]
        );

        $this->dispatcher->process($connectorObj);

        /** @var Promotion[] $promotions */
        $promotions = $connectorObj->getResponseData();

        $collection = new \Ess\M2ePro\Model\Ebay\Promotion\Channel\PromotionCollection();
        foreach ($promotions as $promotion) {
            if (
                $promotion->getStatus() === \Ess\M2ePro\Model\Ebay\Promotion::STATUS_DRAFT
                || $promotion->getStatus() === \Ess\M2ePro\Model\Ebay\Promotion::STATUS_INVALID
                || $promotion->getStatus() === \Ess\M2ePro\Model\Ebay\Promotion::STATUS_ENDED
            ) {
                continue;
            }

            $collection->add($promotion);
        }

        return $collection;
    }
}
