<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails;

class Order
{
    public string $orderId;
    public \DateTime $purchaseDate;
    /** @var Item[] */
    public array $items = [];

    /**
     * @param Item[] $items
     */
    public function __construct(
        string $orderId,
        \DateTime $purchaseDate,
        array $items
    ) {
        $this->orderId = $orderId;
        $this->purchaseDate = $purchaseDate;
        $this->items = $items;
    }
}
