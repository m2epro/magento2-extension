<?php

namespace Ess\M2ePro\Model\Order\Tax;

interface PriceTaxRateInterface
{
    /**
     * @return float|int
     */
    public function getValue();

    /**
     * @return float|int
     */
    public function getNotRoundedValue();

    public function isEnabledRoundingOfValue(): bool;
}
