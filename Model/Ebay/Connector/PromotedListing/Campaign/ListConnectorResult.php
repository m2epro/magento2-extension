<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\PromotedListing\Campaign;

class ListConnectorResult
{
    /** @var \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\Campaign[] */
    private array $campaigns;
    private int $nextPage;

    public function __construct(array $campaigns, int $nextPage)
    {
        $this->campaigns = $campaigns;
        $this->nextPage = $nextPage;
    }

    public function getNextPage(): ?int
    {
        return $this->nextPage;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\PromotedListing\Channel\Dto\Campaign[]
     */
    public function getCampaigns(): array
    {
        return $this->campaigns;
    }
}
