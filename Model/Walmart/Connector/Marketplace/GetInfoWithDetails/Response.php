<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetInfoWithDetails;

class Response
{
    private array $productTypes;
    private \DateTime $lastUpdate;
    private array $productTypesNicks;

    public function __construct(
        array $productTypes,
        array $productTypesNicks,
        \DateTime $lastUpdate
    ) {
        $this->productTypes = $productTypes;
        $this->productTypesNicks = $productTypesNicks;
        $this->lastUpdate = $lastUpdate;
    }

    /**
     * @return list<array{nick: string, title: string}>
     */
    public function getProductTypes(): array
    {
        return $this->productTypes;
    }

    /**
     * @return string[]
     */
    public function getProductTypesNicks(): array
    {
        return $this->productTypesNicks;
    }

    public function getLastUpdate(): \DateTime
    {
        return $this->lastUpdate;
    }
}
