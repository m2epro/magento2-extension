<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign;

class GetItemsConnectorResult
{
    /** @var \Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign\GetItemsConnectorResult[] */
    private array $items;
    private int $nextPage;

    public function __construct(array $items, int $nextPage)
    {
        $this->items = $items;
        $this->nextPage = $nextPage;
    }

    public function getNextPage(): ?int
    {
        return $this->nextPage;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\CampaignItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
