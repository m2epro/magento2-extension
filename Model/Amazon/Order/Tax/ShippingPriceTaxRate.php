<?php

namespace Ess\M2ePro\Model\Amazon\Order\Tax;

class ShippingPriceTaxRate implements \Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface
{
    /** @var float */
    private $taxAmount;
    /** @var float */
    private $shippingPrice;
    /** @var float */
    private $shippingDiscountAmount;
    /** @var bool */
    private $isEnabledRoundingOfValue;

    public function __construct(
        float $taxAmount,
        float $shippingPrice,
        float $shippingDiscountAmount,
        bool $isEnabledRoundingOfValue
    ) {
        $this->taxAmount = $taxAmount;
        $this->shippingPrice = $shippingPrice;
        $this->shippingDiscountAmount = $shippingDiscountAmount;
        $this->isEnabledRoundingOfValue = $isEnabledRoundingOfValue;
    }

    /**
     * @return float|int
     */
    public function getValue()
    {
        $rate = $this->getCalculatedValue();

        if ($rate === 0) {
            return $rate;
        }

        return $this->isEnabledRoundingOfValue
            ? (int)round($rate)
            : round($rate, 4);
    }

    /**
     * @return float|int
     */
    public function getNotRoundedValue()
    {
        $rate = $this->getCalculatedValue();

        return $rate === 0 ? $rate : round($rate, 4);
    }

    /**
     * @return float|int
     */
    private function getCalculatedValue()
    {
        if ($this->taxAmount <= 0) {
            return 0;
        }

        $diff = $this->shippingPrice - $this->shippingDiscountAmount;

        if ($diff <= 0) {
            return 0;
        }

        return ($this->taxAmount / $diff) * 100;
    }

    public function isEnabledRoundingOfValue(): bool
    {
        return $this->isEnabledRoundingOfValue;
    }
}
