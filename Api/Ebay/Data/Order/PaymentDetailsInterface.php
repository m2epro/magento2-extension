<?php

namespace Ess\M2ePro\Api\Ebay\Data\Order;

interface PaymentDetailsInterface
{
    public const DATE_KEY = 'date';
    public const METHOD_KEY = 'method';
    public const STATUS_KEY = 'status';
    public const IS_REFUND_KEY = 'is_refund';

    /**
     * @return string|null
     */
    public function getDate(): ?string;

    /**
     * @return string|null
     */
    public function getMethod(): ?string;

    /**
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @return bool
     */
    public function getIsRefund(): bool;
}
