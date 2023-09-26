<?php

namespace Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria;

class Request
{
    /** @var int */
    private $marketplaceId;
    /** @var string[] */
    private $criteria;

    /**
     * @param int $marketplaceId
     * @param string[] $criteria
     */
    public function __construct(int $marketplaceId, array $criteria)
    {
        $this->marketplaceId = $marketplaceId;
        $this->criteria = $criteria;
    }

    public function getMarketplaceId(): int
    {
        return $this->marketplaceId;
    }

    /**
     * @return string[]
     */
    public function getCriteria(): array
    {
        return $this->criteria;
    }
}
