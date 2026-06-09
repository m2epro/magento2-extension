<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\ReturnDetails;

class Item
{
    public const STATUS_APPROVED = 'Approved';

    public string $itemId;
    public string $sku;
    public string $asin;
    public string $rmaId;
    public string $resolution;
    public int $returnQty;
    public string $trackingId;
    public string $returnReason;
    public \DateTime $returnRequestDate;
    public string $returnRequestStatus;

    public function __construct(
        string $itemId,
        string $sku,
        string $asin,
        string $rmaId,
        string $resolution,
        int $returnQty,
        string $trackingId,
        string $returnReason,
        \DateTime $returnRequestDate,
        string $returnRequestStatus
    ) {
        $this->itemId = $itemId;
        $this->sku = $sku;
        $this->asin = $asin;
        $this->rmaId = $rmaId;
        $this->resolution = $resolution;
        $this->returnQty = $returnQty;
        $this->trackingId = $trackingId;
        $this->returnReason = $returnReason;
        $this->returnRequestDate = $returnRequestDate;
        $this->returnRequestStatus = $returnRequestStatus;
    }
}
