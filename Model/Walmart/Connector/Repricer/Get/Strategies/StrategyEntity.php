<?php

namespace Ess\M2ePro\Model\Walmart\Connector\Repricer\Get\Strategies;

class StrategyEntity
{
    public string $name;
    public string $collectionId;
    public bool $enabled;
    public int $assignedCount;
    public bool $enableForPromotion;
    public bool $restoreSellerPriceWithoutTarget;
    public bool $enableBuyboxMeetExternal;
    public bool $compareWithThirdPartyOfferOnly;
    public array $strategies;

    public function __construct(
        string $name,
        string $collectionId,
        bool $enabled,
        int $assignedCount,
        bool $enableForPromotion,
        bool $restoreSellerPriceWithoutTarget,
        bool $enableBuyboxMeetExternal,
        bool $compareWithThirdPartyOfferOnly,
        array $strategies
    ) {
        $this->name = $name;
        $this->collectionId = $collectionId;
        $this->enabled = $enabled;
        $this->assignedCount = $assignedCount;
        $this->enableForPromotion = $enableForPromotion;
        $this->restoreSellerPriceWithoutTarget = $restoreSellerPriceWithoutTarget;
        $this->enableBuyboxMeetExternal = $enableBuyboxMeetExternal;
        $this->compareWithThirdPartyOfferOnly = $compareWithThirdPartyOfferOnly;
        $this->strategies = $strategies;
    }
}
