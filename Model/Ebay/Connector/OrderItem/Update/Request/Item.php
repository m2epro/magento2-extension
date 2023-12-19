<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Connector\OrderItem\Update\Request;

class Item
{
    /** @var string */
    private $itemId;
    /** @var string */
    private $transactionId;
    /** @var string */
    private $trackingNumber;
    /** @var string */
    private $carrierCode;
    /** @var int */
    private $shippedQty;

    public function __construct(
        string $itemId,
        string $transactionId,
        string $trackingNumber,
        string $carrierCode,
        int $shippedQty
    ) {
        $this->itemId = $itemId;
        $this->transactionId = $transactionId;
        $this->trackingNumber = $trackingNumber;
        $this->carrierCode = $carrierCode;
        $this->shippedQty = $shippedQty;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getTrackingNumber(): string
    {
        return $this->trackingNumber;
    }

    public function getCarrierCode(): string
    {
        return $this->carrierCode;
    }

    public function getShippedQty(): int
    {
        return $this->shippedQty;
    }
}
