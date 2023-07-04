<?php

namespace Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetails;

interface CollectAndRemitInterface
{
    public const TAX_AMOUNT_KEY = 'tax_amount';
    public const SUBTOTAL_AMOUNT_KEY = 'subtotal_amount';
    public const WASTE_RECYCLING_FEE_KEY = 'waste_recycling_fee';
    public const TAX_EXCLUDED_KEY = 'tax_excluded';

    /**
     * @return float
     */
    public function getTaxAmount(): float;

    /**
     * @return float
     */
    public function getSubtotalAmount(): float;

    /**
     * @return float
     */
    public function getWasteRecyclingFee(): float;

    /**
     * @return bool
     */
    public function getTaxExcluded(): bool;
}
