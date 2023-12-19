<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\OrderItem\Update;

class Request
{
    /** @var string|null */
    private $itemId = null;
    /** @var string|null */
    private $transactionId = null;
    /** @var string|null */
    private $orderId = null;
    /** @var Request\Item[] */
    private $items = [];

    public function setItemId(string $itemId): void
    {
        $this->itemId = $itemId;
    }

    public function getItemId(): ?string
    {
        return $this->itemId;
    }

    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function addItem(Request\Item $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return Request\Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
