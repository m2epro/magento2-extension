<?php

namespace Ess\M2ePro\Api\Ebay\Data\Order;

interface TaxDetailsInterface
{
    public const RATE_KEY = 'rate';
    public const AMOUNT_KEY = 'amount';
    public const IS_VAT_KEY = 'is_vat';
    public const INCLUDES_SHIPPING_KEY = 'includes_shipping';
    public const SHIPPING_RATE_KEY = 'shipping_rate';

    /**
     * @return float
     */
    public function getRate(): float;

    /**
     * @return float
     */
    public function getAmount(): float;

    /**
     * @return bool
     */
    public function getIsVat(): bool;

    /**
     * @return bool
     */
    public function getIncludesShipping(): bool;

    /**
     * @return float
     */
    public function getShippingRate(): float;
}
