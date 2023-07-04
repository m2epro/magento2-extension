<?php

namespace Ess\M2ePro\Api\Ebay\Data\Order\OrderItem;

interface TaxDetailsInterface
{
    public const RATE_KEY = 'rate';
    public const AMOUNT_KEY = 'amount';
    public const EBAY_COLLECT_TAXES_KEY = 'ebay_collect_taxes';
    public const COLLECT_AND_REMIT = 'collect_and_remit';

    /**
     * @return float|null
     */
    public function getRate(): float;

    /**
     * @return float|null
     */
    public function getAmount(): float;

    /**
     * @return string|null
     */
    public function getEbayCollectTaxes(): ?string;

    /**
     * @return \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetails\CollectAndRemitInterface
     */
    public function getCollectAndRemit(): \Ess\M2ePro\Api\Ebay\Data\Order\OrderItem\TaxDetails\CollectAndRemitInterface;
}
