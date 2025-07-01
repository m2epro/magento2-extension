<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign;

class DeleteItemsConnectorResult
{
    /**
     * @var \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\Item\ChannelItemResult[]
     */
    private array $items;

    /**
     * @param \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\Item\ChannelItemResult[] $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\Item\ChannelItemResult[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
